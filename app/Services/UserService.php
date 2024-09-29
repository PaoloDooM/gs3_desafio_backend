<?php

namespace App\Services;

use App\Models\User;
use App\Models\Address;
use App\Models\TelephoneNumber;
use App\Exceptions\NotFoundException;
use App\Exceptions\BadParamsException;
use Illuminate\Support\Facades\DB;

class UserService
{
    public static function updateUserById($id, $userData)
    {
        $user = User::find($id);
        if (!$user) {
            throw new NotFoundException('User "' . $id . '" not found');
        }
        $user->update($userData);
    }

    public static function addAddress($user_id, $address)
    {
        try {
            DB::beginTransaction();
            $principalAddresses = Address::where('user_id', '=', $user_id, 'and')->where('principal', '=', true)->get();
            if (count($principalAddresses) <= 0) {
                $address['principal'] = true;
            } else if ($address['principal']) {
                foreach ($principalAddresses as $principalAddress) {
                    $principalAddress->principal = false;
                    $principalAddress->save();
                }
            }
            Address::create([
                'user_id' => $user_id,
                'description' => $address['description'],
                'address' => $address['address'],
                'principal' => $address['principal']
            ]);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public static function removeAddress($user_id, $id)
    {
        $deletedAddresses = Address::where('user_id', '=', $user_id, 'and')->where('id', '=', $id)->delete();
        if (!$deletedAddresses) {
            throw new NotFoundException('Address "' . $id . '" not found for user "' . $user_id . '"');
        }
    }

    public static function updateAddress($user_id, $address)
    {
        try {
            DB::beginTransaction();
            $addresses = Address::where('user_id', '=', $user_id, 'and')->where('id', '=',  $address['id'])->get();
            if (count($addresses) <= 0) {
                throw new NotFoundException('Address "' . $address['id'] . '" not found for user "' . $user_id . '"');
            }
            $principalAddresses = Address::where('user_id', '=', $user_id, 'and')
                ->where('principal', '=', true, 'and')
                ->where('id', '<>', $address['id'])
                ->get();
            if (count($principalAddresses)<=0 && !$address['principal']) {
                throw new BadParamsException('A principal address is required.');
            }
            if (count($principalAddresses) > 0 && $address['principal']) {
                foreach ($principalAddresses as $principalAddress) {
                    $principalAddress->principal = false;
                    $principalAddress->save();
                }
            }
            foreach ($addresses as $savedAddress) {
                $savedAddress->address = $address['address'];
                $savedAddress->description = $address['description'];
                $savedAddress->principal = $address['principal'];
                $savedAddress->save();
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }
    public static function addPhoneNumber($user_id, $phoneNumber)
    {
        try {
            DB::beginTransaction();
            $principalPhoneNumbers = TelephoneNumber::where('user_id', '=', $user_id, 'and')->where('principal', '=', true)->get();
            if (count($principalPhoneNumbers) <= 0) {
                $phoneNumber['principal'] = true;
            } else if ($phoneNumber['principal']) {
                foreach ($principalPhoneNumbers as $principalPhoneNumber) {
                    $principalPhoneNumber->principal = false;
                    $principalPhoneNumber->save();
                }
            }
            TelephoneNumber::create([
                'user_id' => $user_id,
                'description' => $phoneNumber['description'],
                'number' => $phoneNumber['number'],
                'principal' => $phoneNumber['principal']
            ]);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public static function removePhoneNumber($user_id, $id)
    {
        $deletedPhoneNumbers = TelephoneNumber::where('user_id', '=', $user_id, 'and')->where('id', '=', $id)->delete();
        if (!$deletedPhoneNumbers) {
            throw new NotFoundException('Phone number "' . $id . '" not found for user "' . $user_id . '"');
        }
    }

    public static function updatePhoneNumber($user_id, $phoneNumber)
    {
        try {
            DB::beginTransaction();
            $phoneNumbers = TelephoneNumber::where('user_id', '=', $user_id, 'and')->where('id', '=', $phoneNumber['id'])->get();
            if (count($phoneNumbers) <= 0) {
                throw new NotFoundException('Phone number "' . $phoneNumber['id'] . '" not found for user "' . $user_id . '"');
            }
            $principalPhoneNumbers = TelephoneNumber::where('user_id', '=', $user_id, 'and')
                ->where('principal', '=', true, 'and')
                ->where('id', '<>', $phoneNumber['id'])
                ->get();
            if (count($principalPhoneNumbers)<=0 && !$phoneNumber['principal']) {
                throw new BadParamsException('A principal phone number is required');
            }
            if (count($principalPhoneNumbers) > 0 && $phoneNumber['principal']) {
                foreach ($principalPhoneNumbers as $principalPhoneNumber) {
                    $principalPhoneNumber->principal = false;
                    $principalPhoneNumber->save();
                }
            }
            foreach ($phoneNumbers as $savedPhoneNumber) {
                $savedPhoneNumber->number = $phoneNumber['number'];
                $savedPhoneNumber->description = $phoneNumber['description'];
                $savedPhoneNumber->principal = $phoneNumber['principal'];
                $savedPhoneNumber->save();
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }
}
