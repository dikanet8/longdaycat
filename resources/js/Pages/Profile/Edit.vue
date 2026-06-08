<script setup>
import AppLayout from'@/Layouts/AppLayout.vue';
import DeleteUserForm from'./Partials/DeleteUserForm.vue';
import UpdatePasswordForm from'./Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from'./Partials/UpdateProfileInformationForm.vue';
import { Head } from'@inertiajs/vue3';

defineProps({
  mustVerifyEmail: {
    type: Boolean,
  },
  status: {
    type: String,
  },
});
</script>

<template>
  <Head title="Pengaturan Akun" />

  <AppLayout>
    <div class="space-y-6 max-w-4xl animate-in fade-in slide-in-from-bottom-4 duration-700">
      <!-- Header Section (Filament Style) -->
      <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
        <div>
          <h1 class="text-2xl font-black text-slate-900 tracking-tight mb-1">Pengaturan Akun</h1>
          <p class="text-slate-500 text-sm font-semibold">Kelola profil, keamanan, dan preferensi akun Anda.</p>
        </div>
      </div>

      <!-- Profile Info Section -->
      <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <div class="flex items-center gap-4 mb-6 pb-4 border-b border-slate-100">
          <div class="w-10 h-10 bg-blue-50 rounded-xl flex-shrink-0 flex items-center justify-center text-blue-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900">Informasi Profil</h3>
            <p class="text-slate-500 text-xs font-medium">Perbarui nama lengkap dan alamat email akun Anda.</p>
          </div>
        </div>
        <UpdateProfileInformationForm
          :must-verify-email="mustVerifyEmail"
          :status="status"
        />
      </div>

      <!-- Security Section -->
      <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <div class="flex items-center gap-4 mb-6 pb-4 border-b border-slate-100">
          <div class="w-10 h-10 bg-emerald-50 rounded-xl flex-shrink-0 flex items-center justify-center text-emerald-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900">Keamanan Akun</h3>
            <p class="text-slate-500 text-xs font-medium">Pastikan akun Anda tetap aman dengan menggunakan kata sandi yang kuat.</p>
          </div>
        </div>
        <UpdatePasswordForm />
      </div>

      <!-- Danger Zone Section -->
      <div class="bg-red-50/10 border border-red-200 rounded-xl p-6 shadow-sm">
        <div class="flex items-center gap-4 mb-6 pb-4 border-b border-red-100">
          <div class="w-10 h-10 bg-red-50 rounded-xl flex-shrink-0 flex items-center justify-center text-red-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </div>
          <div>
            <h3 class="text-base font-bold text-red-800">Zona Bahaya</h3>
            <p class="text-red-600/80 text-xs font-medium">Hapus akun Anda secara permanen beserta semua datanya.</p>
          </div>
        </div>
        <DeleteUserForm />
      </div>
    </div>
  </AppLayout>
</template>
