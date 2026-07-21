<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Cek apakah user dengan google_id tersebut sudah ada
            $user = User::where('google_id', $googleUser->getId())->first();
            
            if ($user) {
                // Jika sudah ada, langsung login
                Auth::login($user);
            } else {
                // Jika belum, cek apakah email sudah terdaftar sebelumnya (lewat registrasi manual)
                $existingUser = User::where('email', $googleUser->getEmail())->first();
                
                if ($existingUser) {
                    // Update user tersebut dengan google_id
                    $existingUser->update([
                        'google_id' => $googleUser->getId(),
                        'email_verified_at' => $existingUser->email_verified_at ?? now(), // asumsikan google valid
                    ]);
                    Auth::login($existingUser);
                } else {
                    // Buat user baru sebagai operator toko tanpa admin_id (bisa disesuaikan nanti)
                    // Atau secara default jadi admin tokonya sendiri
                    $newUser = User::create([
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'google_id' => $googleUser->getId(),
                        'password' => bcrypt(Str::random(16)), // password acak
                        'role' => 'admin', // default ke admin toko agar bisa kelola sendiri
                        'is_active' => true,
                        'email_verified_at' => now(),
                    ]);
                    
                    Auth::login($newUser);
                }
            }
            
            return redirect()->route('dashboard');
            
        } catch (\Exception $e) {
            return redirect()->route('login')->with('status', 'Gagal login menggunakan Google. Silakan coba lagi.');
        }
    }
}
