<?php

namespace App\Services;

use App\Models\InquiryModel;
use App\Models\InquiryReplyModel;
use Config\Database;

/**
 * One ongoing support conversation per user. A customer never spawns a new
 * thread per request — every message (a fresh request or a reply) lands in their
 * single conversation, so support has one place to manage each customer. The
 * conversation is an `inquiries` row (its `message` = the opening message) and
 * every later message is an `inquiry_replies` row.
 */
class SupportConversation
{
    /** The user's conversation (latest inquiry by id, matched by account or email), or null. */
    public function getFor(array $user): ?array
    {
        $b = Database::connect()->table('inquiries');
        $b->groupStart()->where('user_id', (int) $user['id']);
        if (! empty($user['email'])) {
            $b->orWhere('email', $user['email']);
        }
        $b->groupEnd();
        return $b->orderBy('id', 'DESC')->limit(1)->get()->getRowArray() ?: null;
    }

    /** Chronological messages: the opening inquiry message + every reply. */
    public function messages(array $conv): array
    {
        $out = [[
            'sender'  => 'customer',
            'name'    => $conv['name'],
            'message' => $conv['message'],
            'at'      => $conv['created_at'],
        ]];
        foreach ((new InquiryReplyModel())->thread((int) $conv['id']) as $r) {
            $out[] = ['sender' => $r['sender_type'], 'name' => $r['name'], 'message' => $r['message'], 'at' => $r['created_at']];
        }
        return $out;
    }

    /**
     * Append a customer message to their conversation, creating it on first use.
     * Re-opens the thread (status = new) so support sees the new activity.
     *
     * @param array{ip?:string,ua?:string} $meta
     * @return int conversation (inquiry) id
     */
    public function appendCustomer(array $user, string $message, string $subject = 'support', array $meta = []): int
    {
        $message = mb_substr($message, 0, 5000);
        $inqM    = new InquiryModel();
        $conv    = $this->getFor($user);

        if (! $conv) {
            $inqM->insert([
                'user_id'         => (int) $user['id'],
                'name'            => mb_substr((string) ($user['name'] ?? ''), 0, 120),
                'email'           => mb_substr((string) ($user['email'] ?? ''), 0, 190),
                'phone'           => isset($user['mobile']) ? mb_substr((string) $user['mobile'], 0, 20) : null,
                'subject'         => mb_substr($subject !== '' ? $subject : 'support', 0, 40),
                'message'         => $message,
                'status'          => 'new',
                'customer_unread' => 0,
                'ip_address'      => $meta['ip'] ?? null,
                'user_agent'      => isset($meta['ua']) ? mb_substr((string) $meta['ua'], 0, 255) : null,
            ]);
            return (int) $inqM->getInsertID();
        }

        (new InquiryReplyModel())->insert([
            'inquiry_id'  => (int) $conv['id'],
            'sender_type' => 'customer',
            'user_id'     => (int) $user['id'],
            'name'        => mb_substr((string) ($user['name'] ?? 'You'), 0, 150),
            'message'     => $message,
        ]);
        $inqM->update((int) $conv['id'], ['status' => 'new', 'customer_unread' => 0]);
        return (int) $conv['id'];
    }
}
