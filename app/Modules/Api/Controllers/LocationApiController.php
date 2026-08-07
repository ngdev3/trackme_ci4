<?php

namespace Modules\Api\Controllers;

use App\Models\LoginLogModel;

/**
 * Precise device location for the current session.
 *
 *   POST /api/v1/location   (Bearer)   {latitude, longitude, accuracy?, label?}
 *
 * The mobile app calls this right after login once the user grants location
 * access. It attaches the GPS fix to the caller's most-recent successful login
 * record (login_logs), upgrading the coarse IP location to a precise one so the
 * super-admin login-logs view can show exactly where the user signed in from.
 * Sending location is optional; a login still works (and keeps its IP location)
 * if the user declines.
 */
class LocationApiController extends BaseApiController
{
    public function update()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }

        $lat = $this->input('latitude');
        $lng = $this->input('longitude');
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return $this->failValidationErrors('latitude and longitude are required numbers.');
        }
        $lat = (float) $lat;
        $lng = (float) $lng;
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return $this->failValidationErrors('latitude/longitude out of range.');
        }

        $accuracy = $this->input('accuracy');
        $accuracy = is_numeric($accuracy) ? (int) round((float) $accuracy) : null;
        $label    = trim((string) ($this->input('label', $this->input('location_label', ''))));
        $label    = $label !== '' ? mb_substr($label, 0, 180) : null;

        $model = new LoginLogModel();
        $row   = $model
            ->where('user_id', (int) $user['id'])
            ->where('status', 'success')
            ->orderBy('id', 'DESC')
            ->first();

        if (! $row) {
            // No login record to attach to (shouldn't happen for an authed call).
            return $this->respond(['status' => 'success', 'attached' => false]);
        }

        $data = [
            'latitude'          => $lat,
            'longitude'         => $lng,
            'location_accuracy' => $accuracy,
            'location_source'   => 'gps',
        ];
        if ($label !== null) {
            $data['location_label'] = $label;
        }

        $model->update((int) $row['id'], $data);

        return $this->respond([
            'status'   => 'success',
            'attached' => true,
            'location' => [
                'latitude'  => $lat,
                'longitude' => $lng,
                'accuracy'  => $accuracy,
                'source'    => 'gps',
                'label'     => $label,
            ],
        ]);
    }
}
