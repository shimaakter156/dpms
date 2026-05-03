<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Menu\UserPermission;
use App\Models\User;
use App\Models\UserMenu;
use App\Services\MenuService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class SettingController extends Controller
{

    protected $menuService;
    public function __construct(MenuService $menuService){
        $this->menuService = $menuService;
    }
    public function getMenu()
    {
        $userId = auth()->id();
        $permissions = DB::table('vw_UserPermissions')
            ->where('UserID', $userId)
            ->where('CanView', 1)
            ->orderBy('ParentMenuID')
            ->orderBy('SortOrder')
            ->get();


        $menu = $permissions->groupBy('ParentMenuID')->map(function ($items) {

            $first = $items->first();
            return [
                'id' => $first->ParentMenuID,
                'name' => $first->ParentMenuName,
                'icon' => $first->ParentMenuIcon,
                'url' => $first->ParentMenuURL,
                'children' => $items->map(function ($item) {
                    return [
                        'id' => $item->MenuID,
                        'name' => $item->MenuName,
                        'url' => $item->MenuURL,
                        'code' => $item->MenuCode
                    ];
                })
            ];
        })->values();



        return response()->json($menu);
    }


    public function menuPermission(Request $request)
    {
        $path = $request->name;
        $user = JWTAuth::parseToken()->authenticate();
        $checkPermission = UserMenu::join('MenuItem', 'MenuItem.ID', 'UserMenu.MenuItemId')
            ->where('UserMenu.UserId', $user->ID)
            ->where('MenuItem.Link', $path)
            ->exists();
        if ($checkPermission) {
            return response()->json(['message' => "menu found"], 200);
        } else {
            return response()->json(['message' => "menu not found"], 400);
        }
    }

    public function appSupportingData()
    {
        try {
            $auth = Auth::user();
            $query = Menu::select('Menus.*');
//            if ($auth->RoleID === 'RepresentativeUser' || $auth->RoleID === 'GeneralUser') {
//                $query->where('MenuID','!=','Users');
//            }
            $data = $query->with('subMenus')
                ->orderBy('MenuOrder','asc')
                ->get();
            return response()->json([
                'status' => 'success',
                'menus' => $data,
                'user' => User::where('UserID',$auth->UserID)
                    ->with('userType')
                    ->first(),
//                'particulars' => Particular::where('Active',1)->get()
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function imageUpload($image, $namePrefix, $destination)
    {

        list($type, $file) = explode(';', $image);
        list(, $extension) = explode('/', $type);
        list(, $file) = explode(',', $file);
        $fileNameToStore = $namePrefix . strtotime(Carbon::now()) . rand(0, 100000000) . '.' . $extension;
        $source = fopen($image, 'r');
        $destination = fopen($destination . $fileNameToStore, 'w');
        stream_copy_to_stream($source, $destination);
        fclose($source);
        fclose($destination);
        return $fileNameToStore;
    }
}
