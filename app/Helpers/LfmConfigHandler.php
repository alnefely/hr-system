<?php

namespace App\Helpers;

class LfmConfigHandler
{
    public function userField()
    {
        if (auth('admin')->check()) {
            $admin = auth('admin')->user();
            if ($admin->main) {
                return '';
            }
            return 'admins/'.auth('admin')->id();
        }

    }
}