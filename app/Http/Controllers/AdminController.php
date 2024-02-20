<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Helpers\ApiResponse;

class AdminController extends Controller
{
    public function unsuspendMerchant($id)
    {
        $merchant = Merchant::where('id',$id)->first();

        if (is_null($merchant)) {
            return ApiResponse::error('Merchant Data Not Found', 404);
        }

        if ($merchant->is_suspended == 1) {
            $merchant->is_suspended = 0;
            $merchant->save();
            return ApiResponse::success([], 'Merchant Was Successfully Unsuspended', 200);
        } else {
            return ApiResponse::error('Merchant Data is Not Suspended', 400);
        }
    }
}
