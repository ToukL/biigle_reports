<?php

namespace Biigle\Modules\Export\Http\Controllers\Api;

use Biigle\User;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Biigle\Http\Controllers\Api\Controller;

class SettingsController extends Controller
{
    /**
     * Validation rules for the settings handled by this controller.
     *
     * Only setting keys that are present in this array will be accepted.
     *
     * @var array
     */
    const VALIDATION_RULES = [
        'report_notifications' => 'filled|in:email,web',
    ];

    /**
     * Update the user settings for reports
     *
     * @api {post} users/my/settings/export Update the user settings for reports
     * @apiGroup Users
     * @apiName StoreUsersExportSettings
     * @apiPermission user
     *
     * @apiParam (Optional arguments) {String} report_notifications Set to `'email'` or `'web'` to receive notifications for finished reports either via email or the BIIGLE notification center.
     *
     * @param Request $request
     * @param Guard $auth
     * @param int $id Volume ID
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Guard $auth)
    {
        $this->validate($request, self::VALIDATION_RULES);
        $settings = $request->only(array_keys(self::VALIDATION_RULES));
        if (config('export.notifications.allow_user_settings') === false) {
            unset($settings['report_notifications']);
        }
        $auth->user()->setSettings($settings);
    }
}