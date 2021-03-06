<?php

namespace App\Http\Controllers;

use App\Bid;
use App\Sales;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Symfony\Component\Console\Input\Input;

class ProfilesController extends Controller
{
    public function index(User $user)
    {
        $qcondition = ['user_id' => $user->id];
        $bids = Bid::where($qcondition)->get();

        $expired = array();
        $live = array();
        $expiredSelf = array();
        $liveSelf = array();

        foreach ($bids as $bid){
            $sale = Sales::find($bid->sales_id);
            $saved = new Carbon($sale->endOfAuction);
            if ($saved->gt(now())){
                array_push($live, $sale);
            }
            else{
                if ($sale->buyer_id == $user->id){
                    array_push($expired, $sale);
                }

            }
        }

        $qcondition = ['user_id' => $user->id];
        foreach (Sales::where($qcondition)->get() as $sale){
            $saved = new Carbon($sale->endOfAuction);
            if (!($saved->gt(now()))){
                array_push($expiredSelf, $sale);
            }
            else{
                array_push($liveSelf, $sale);
            }
        }

        $data = array();
        array_push($data, $user);
        array_push($data, $expired);
        array_push($data, $live);
        array_push($data, $expiredSelf);
        array_push($data, $liveSelf);

        return view('profiles.index', compact('data'));
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user->profile);
        return view('profiles.edit', compact('user'));
    }

    public function update(User $user)
    {
        $this->authorize('update', $user->profile);

        $data = request()->validate([
            'description' => 'required',
            'url' => '',
            'image' => '',
        ]);

        $data2 = request()->input('newsletter2');

        if (request('image'))
        {
            $imagepath = request('image')->store('uploads', 'public');

            $image = Image::make(public_path("storage/{$imagepath}"))->fit(1000,1000);
            $image->save();

            auth()->user()->profile->update(array_merge(
                $data,
                ['newsletter' => $data2],
                ['image' => $imagepath]
            ));
        }
        else{
            auth()->user()->profile->update(array_merge(
                $data,
                ['newsletter' => $data2]
            ));
        }


        return redirect("profile/{$user->id}");
    }
}
