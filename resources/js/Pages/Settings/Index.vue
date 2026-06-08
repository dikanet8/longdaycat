<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  setting: Object
});

const form = useForm({
  nama_toko: props.setting?.nama_toko || '',
  alamat_toko: props.setting?.alamat_toko || '',
  telepon_toko: props.setting?.telepon_toko || '',
  deskripsi_struk: props.setting?.deskripsi_struk || '',
  logo_toko: null,
  _method: 'POST'
});

const logoPreview = ref(props.setting?.logo_toko || null);

const handleLogoUpload = (e) => {
  const file = e.target.files[0];
  if (file) {
    form.logo_toko = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      logoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const submit = () => {
  form.post(route('settings.system.update'), {
    preserveScroll: true,
    onSuccess: () => {
      // Optional: Add a local toast notification here if desired
    }
  });
};
</script>

<template>
  <Head title="Pengaturan Sistem" />

  <AppLayout>
    <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
      
      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 md:gap-6">
        <div>
          <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Pengaturan Sistem</h1>
          <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium text-xs md:text-sm">Konfigurasi profil dan detail informasi toko Anda.</p>
        </div>
      </div>

      <!-- Main Form Area -->
      <div class="bg-white dark:bg-slate-900 rounded-3xl md:rounded-xl shadow-sm border border-slate-200 dark:border-white/5 overflow-hidden">
        <form @submit.prevent="submit" class="p-6 md:p-8 space-y-8">
          
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Logo -->
            <div class="lg:col-span-1 space-y-4">
              <label class="block text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Logo Toko</label>
              <div class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-300 dark:border-white/10 rounded-2xl md:rounded-xl bg-slate-50 dark:bg-slate-800/50 group hover:border-blue-500 transition-colors relative overflow-hidden">
                <template v-if="logoPreview">
                  <img :src="logoPreview" alt="Logo Preview" class="w-32 h-32 object-contain rounded-xl shadow-sm bg-white dark:bg-slate-900 mb-4" />
                </template>
                <template v-else>
                  <div class="w-32 h-32 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4 text-slate-400">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                </template>
                
                <input 
                  type="file" 
                  accept="image/*" 
                  class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                  @change="handleLogoUpload"
                >
                <div class="text-center relative z-0">
                  <p class="text-sm font-bold text-blue-600 dark:text-blue-400 group-hover:underline">Pilih Gambar</p>
                  <p class="text-xs text-slate-500 mt-1">Maks. 2MB (PNG, JPG)</p>
                </div>
              </div>
              <p v-if="form.errors.logo_toko" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.logo_toko }}</p>
            </div>

            <!-- Right Column: Text Fields -->
            <div class="lg:col-span-2 space-y-6">
              <!-- Store Name -->
              <div>
                <label class="block text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider mb-2">Nama Toko *</label>
                <input 
                  v-model="form.nama_toko"
                  type="text" 
                  class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-xl py-3 px-4 text-sm font-medium text-slate-700 dark:text-slate-200 ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-blue-500 transition-all placeholder:text-slate-400"
                  placeholder="Misal: SumoPod Mini"
                  required
                >
                <p v-if="form.errors.nama_toko" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.nama_toko }}</p>
              </div>

              <!-- Phone -->
              <div>
                <label class="block text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider mb-2">Nomor Telepon</label>
                <input 
                  v-model="form.telepon_toko"
                  type="text" 
                  class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-xl py-3 px-4 text-sm font-medium text-slate-700 dark:text-slate-200 ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-blue-500 transition-all placeholder:text-slate-400"
                  placeholder="Misal: 081234567890"
                >
                <p v-if="form.errors.telepon_toko" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.telepon_toko }}</p>
              </div>

              <!-- Address -->
              <div>
                <label class="block text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider mb-2">Alamat Toko</label>
                <textarea 
                  v-model="form.alamat_toko"
                  rows="3"
                  class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-xl py-3 px-4 text-sm font-medium text-slate-700 dark:text-slate-200 ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-blue-500 transition-all placeholder:text-slate-400 resize-none"
                  placeholder="Masukkan alamat lengkap toko"
                ></textarea>
                <p v-if="form.errors.alamat_toko" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.alamat_toko }}</p>
              </div>

              <!-- Receipt Footer -->
              <div>
                <label class="block text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider mb-2">Pesan di Struk (Footer)</label>
                <textarea 
                  v-model="form.deskripsi_struk"
                  rows="2"
                  class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-xl py-3 px-4 text-sm font-medium text-slate-700 dark:text-slate-200 ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-blue-500 transition-all placeholder:text-slate-400 resize-none"
                  placeholder="Misal: Terima kasih atas kunjungan Anda!"
                ></textarea>
                <p v-if="form.errors.deskripsi_struk" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.deskripsi_struk }}</p>
              </div>

            </div>
          </div>

          <!-- Form Actions -->
          <div class="pt-6 mt-8 border-t border-slate-100 dark:border-white/5 flex items-center justify-end gap-3">
            <button 
              type="button"
              @click="form.reset()"
              class="px-6 py-2.5 text-xs font-black text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 uppercase tracking-widest transition-colors"
            >
              Batal
            </button>
            <button 
              type="submit"
              :disabled="form.processing"
              class="flex items-center gap-2 px-8 py-3 bg-blue-600 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-lg shadow-blue-500/20 hover:bg-blue-700 active:scale-95 transition-all disabled:opacity-70 disabled:cursor-not-allowed"
            >
              <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>Simpan Perubahan</span>
            </button>
          </div>

        </form>
      </div>
      
    </div>
  </AppLayout>
</template>
