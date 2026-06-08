<script setup>
import InputError from'@/Components/InputError.vue';
import Modal from'@/Components/Modal.vue';
import { useForm } from'@inertiajs/vue3';
import { nextTick, ref } from'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
  password:'',
});

const confirmUserDeletion = () => {
  confirmingUserDeletion.value = true;
  nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
  form.delete(route('profile.destroy'), {
    preserveScroll: true,
    onSuccess: () => closeModal(),
    onError: () => passwordInput.value.focus(),
    onFinish: () => form.reset(),
  });
};

const closeModal = () => {
  confirmingUserDeletion.value = false;
  form.clearErrors();
  form.reset();
};
</script>

<template>
  <section>
    <div class="p-6 bg-red-50 border border-red-200 rounded-xl">
      <h4 class="text-base font-bold text-red-800 mb-2">Hapus Akun Permanen</h4>
      <p class="text-sm text-red-700 mb-6 leading-relaxed">
        Setelah akun Anda dihapus, semua sumber daya dan data di dalamnya akan dihapus secara permanen. Sebelum menghapus akun Anda, harap unduh data atau informasi apa pun yang ingin Anda simpan.
      </p>

      <button 
        @click="confirmUserDeletion"
        class="w-full sm:w-auto px-5 py-2.5 bg-red-600 hover:bg-red-500 text-white font-bold text-sm rounded-xl transition-all shadow-md shadow-red-600/10 active:scale-[0.98] flex items-center justify-center gap-2 cursor-pointer"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        <span>Hapus Akun Saya</span>
      </button>
    </div>

    <Modal :show="confirmingUserDeletion" @close="closeModal">
      <div class="p-8 text-left">
        <h2 class="text-xl font-black text-slate-900 mb-2">
          Apakah Anda yakin?
        </h2>

        <p class="text-slate-500 text-sm mb-6 leading-relaxed">
          Tindakan ini tidak dapat dibatalkan. Harap masukkan kata sandi Anda untuk mengonfirmasi bahwa Anda ingin menghapus akun Anda secara permanen.
        </p>

        <div class="space-y-2">
          <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-500 ml-1">Kata Sandi</label>
          <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-red-500 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </div>
            <input
              id="password"
              ref="passwordInput"
              v-model="form.password"
              type="password"
              class="block w-full pl-12 pr-4 py-3 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 text-slate-800 text-sm transition-all shadow-sm"
              placeholder="Kata sandi Anda"
              @keyup.enter="deleteUser"
            />
          </div>
          <InputError :message="form.errors.password" class="mt-2" />
        </div>

        <div class="mt-8 flex items-center justify-end gap-3">
          <button 
            @click="closeModal"
            class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm rounded-xl transition-all active:scale-[0.98] cursor-pointer"
          >
            Batal
          </button>

          <button 
            @click="deleteUser"
            :disabled="form.processing"
            class="px-6 py-2.5 bg-red-600 hover:bg-red-500 disabled:opacity-50 text-white font-bold text-sm rounded-xl transition-all shadow-md shadow-red-600/10 active:scale-[0.98] flex items-center gap-2 cursor-pointer"
          >
            <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Hapus Akun</span>
          </button>
        </div>
      </div>
    </Modal>
  </section>
</template>
