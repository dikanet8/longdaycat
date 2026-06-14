<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $setting = Setting::first();
        
        if (!$setting) {
            $setting = Setting::create([
                'nama_toko' => 'Sistem Kasir Pintar',
                'alamat_toko' => '',
                'telepon_toko' => '',
                'deskripsi_struk' => 'Terima kasih atas kunjungan Anda!',
                'sma_periode' => 7,
            ]);
        }

        return Inertia::render('Settings/Index', [
            'setting' => $setting
        ]);
    }

    public function update(Request $request)
    {
        $setting = Setting::first();

        $validated = $request->validate([
            'nama_toko' => 'required|string|max:255',
            'alamat_toko' => 'nullable|string',
            'telepon_toko' => 'nullable|string|max:50',
            'deskripsi_struk' => 'nullable|string',
            'logo_toko' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'sma_periode' => 'required|integer|min:1|max:365'
        ]);

        if ($request->hasFile('logo_toko')) {
            // Delete old logo if exists
            if ($setting->logo_toko) {
                $oldPath = str_replace('/storage/', '', $setting->logo_toko);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('logo_toko')->store('settings', 'public');
            $validated['logo_toko'] = '/storage/' . $path;
        }

        $setting->update($validated);

        return redirect()->back()->with('success', 'Pengaturan berhasil diperbarui!');
    }
}
