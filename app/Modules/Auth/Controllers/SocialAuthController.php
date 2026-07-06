<?php

namespace Modules\Auth\Controllers;

use App\Controllers\BaseController;
use App\Libraries\OAuth\OAuthException;
use App\Libraries\OAuth\OAuthManager;

/**
 * Social sign-in (OAuth 2.0). Provider-agnostic: the {provider} segment selects
 * a configured provider (currently Google) via OAuthManager, so new providers
 * need no controller/route changes.
 *
 *   GET /auth/{provider}           → send the user to the provider's consent screen
 *   GET /auth/{provider}/callback  → handle the redirect back, log the user in
 */
class SocialAuthController extends BaseController
{
    /**
     * Step 1 — redirect to the provider's authorization endpoint with an
     * anti-CSRF `state` token stored in the session.
     */
    public function redirect(string $provider)
    {
        try {
            $prov = (new OAuthManager())->provider($provider);
        } catch (OAuthException $e) {
            return redirect()->to(site_url('login'))->with('error', $e->getMessage());
        }

        $state = bin2hex(random_bytes(16));
        session()->set([
            'oauth_state'    => $state,
            'oauth_provider' => $provider,
            'oauth_mode'     => 'login',
        ]);

        return redirect()->to($prov->getAuthorizationUrl($state));
    }

    /**
     * Start linking a provider to the CURRENT (already signed-in) account,
     * from the profile page. Same callback URL — distinguished by oauth_mode.
     */
    public function link(string $provider)
    {
        try {
            $prov = (new OAuthManager())->provider($provider);
        } catch (OAuthException $e) {
            return redirect()->to(site_url('profile'))->with('error', $e->getMessage());
        }

        $state = bin2hex(random_bytes(16));
        session()->set([
            'oauth_state'     => $state,
            'oauth_provider'  => $provider,
            'oauth_mode'      => 'link',
            'oauth_link_user' => auth()->id(),
        ]);

        return redirect()->to($prov->getAuthorizationUrl($state));
    }

    /**
     * Disconnect a linked provider from the current account (POST + CSRF).
     */
    public function unlink(string $provider)
    {
        [$ok, $message] = auth()->unlinkOAuthAccount((int) auth()->id(), $provider);
        activity_log('Profile', 'Edit', ($ok ? 'Disconnected ' : 'Failed to disconnect ') . ucfirst($provider));

        return redirect()->to(site_url('profile'))->with($ok ? 'success' : 'error', $message);
    }

    /**
     * Step 2 — the provider redirects here. Validate state, exchange the code,
     * verify the token, and establish the session. Every failure mode degrades
     * to a friendly flash message on the login page.
     */
    public function callback(string $provider)
    {
        $session = session();
        // 'link' returns to the profile page; 'login' to the login page.
        $isLink  = $session->get('oauth_mode') === 'link';
        $failUrl = site_url($isLink ? 'profile' : 'login');

        $clear = static function () use ($session) {
            $session->remove(['oauth_state', 'oauth_provider', 'oauth_mode', 'oauth_link_user']);
        };

        // Provider-reported error (user cancelled consent, etc.).
        if ($err = (string) $this->request->getGet('error')) {
            $clear();
            $message = $err === 'access_denied'
                ? ($isLink ? 'Connection was cancelled.' : 'Sign-in was cancelled.')
                : 'The request was not completed. Please try again.';
            return redirect()->to($failUrl)->with('error', $message);
        }

        // Anti-CSRF: the returned state must match what we stored.
        $state = (string) $this->request->getGet('state');
        if ($state === '' || ! hash_equals((string) $session->get('oauth_state'), $state)
            || $provider !== $session->get('oauth_provider')) {
            $clear();
            return redirect()->to($failUrl)->with('error', 'Your session expired. Please try again.');
        }

        $code = (string) $this->request->getGet('code');
        if ($code === '') {
            $clear();
            return redirect()->to($failUrl)->with('error', 'No authorization code was returned. Please try again.');
        }

        try {
            $profile = (new OAuthManager())->provider($provider)->fetchUser($code);

            if ($isLink) {
                $userId = (int) $session->get('oauth_link_user');
                $clear();
                if (! auth()->check() || auth()->id() !== $userId) {
                    return redirect()->to(site_url('login'))->with('error', 'Please sign in again to link your account.');
                }
                [$ok, $message] = auth()->linkOAuthAccount($userId, $profile);
                activity_log('Profile', 'Edit', ($ok ? 'Connected ' : 'Failed to connect ') . ucfirst($provider));
                return redirect()->to(site_url('profile'))->with($ok ? 'success' : 'error', $message);
            }

            $clear();
            [$ok, $message] = auth()->loginWithOAuth($profile);
        } catch (OAuthException $e) {
            $clear();
            return redirect()->to($failUrl)->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            $clear();
            log_message('error', '[OAuth] callback fatal: ' . $e->getMessage());
            return redirect()->to($failUrl)->with('error', 'Something went wrong. Please try again.');
        }

        if (! $ok) {
            return redirect()->to(site_url('login'))->with('error', $message);
        }

        activity_log('Auth', 'Login', 'User logged in via ' . ucfirst($provider));
        return redirect()->to(site_url('dashboard'))->with('success', $message);
    }
}
