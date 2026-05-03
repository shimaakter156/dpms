<?php

namespace App\Services;

use App\Models\Menu\UserPermission;

class MenuService
{

    public function getMenuByUserID($userID){
        $menu = UserPermission::where('UserID',$userID)->get();
        return $menu;
    }
}