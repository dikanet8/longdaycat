<script setup>
import { useForm, Head, Link } from'@inertiajs/vue3';

const form = useForm({
  email:'',
  password:'',
  remember: false,
});

const submit = () => {
  form.email = form.email.trim();
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <Head title="Login" />

  <div class="min-h-screen bg-slate-100 flex font-sans relative overflow-hidden">
    <!-- Left Side: Brand Presentation (Full height) -->
    <div class="hidden md:flex md:w-1/2 bg-gradient-to-br from-slate-50 to-blue-50/50 border-r border-slate-200 flex-col items-center justify-center p-16 h-screen relative">
      <!-- Brand Centered -->
      <div class="flex flex-col items-center gap-8 animate-fade-in-up">
        <div class="p-8 bg-white rounded-[2rem] shadow-2xl shadow-blue-900/5 border border-white/60 backdrop-blur-xl">
          <img src="/logo.png" alt="Logo" class="w-48 h-48 md:w-56 md:h-56 object-contain drop-shadow-sm transition-transform hover:scale-105 duration-500" />
        </div>
        <span class="font-black text-4xl tracking-tight text-slate-900 uppercase">Longdaycat.Co</span>
      </div>

      <!-- Footer info -->
      <p class="absolute bottom-8 text-xs text-slate-400 font-bold tracking-wide">
        &copy; 2026 Longdaycat.Co. Hak Cipta Dilindungi.
      </p>
    </div>

    <!-- Right Side: Login Form (Full height, white background) -->
    <div class="w-full md:w-1/2 flex flex-col justify-center px-6 sm:px-16 lg:px-24 h-screen overflow-y-auto bg-white relative z-10">
      <div class="w-full max-w-md mx-auto py-8">
        <div class="mb-10">
          <!-- Mobile Brand Logo (Visible on mobile only) -->
          <div class="flex items-center gap-2 mb-8 md:hidden">
            <img src="/logo.png" alt="Logo" class="w-8 h-8 object-contain" />
            <span class="font-black text-xl text-slate-900 tracking-tight">Longdaycat.Co</span>
          </div>
          
          <h1 class="text-3xl sm:text-4xl font-black text-slate-900 mb-3 tracking-tight">Selamat Datang</h1>
          <p class="text-slate-500 text-sm font-semibold">Masuk ke akun Anda untuk mulai mengelola transaksi toko</p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
          <div>
            <label class="block text-xs font-black uppercase tracking-wider text-slate-500 mb-2">Alamat Email</label>
            <input
              v-model="form.email"
              type="email"
              class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm transition-all shadow-sm"
              placeholder="nama@email.com"
              required
            />
            <div v-if="form.errors.email" class="text-red-600 text-xs mt-1.5 font-bold">{{ form.errors.email }}</div>
          </div>

          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="text-xs font-black uppercase tracking-wider text-slate-500">Kata Sandi</label>
              <a href="#" class="text-[11px] font-bold text-blue-600 hover:text-blue-500 transition-colors">Lupa Kata Sandi?</a>
            </div>
            <input
              v-model="form.password"
              type="password"
              class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm transition-all shadow-sm"
              placeholder="••••••••"
              required
            />
            <div v-if="form.errors.password" class="text-red-600 text-xs mt-1.5 font-bold">{{ form.errors.password }}</div>
          </div>

          <div class="flex items-center">
            <input
              v-model="form.remember"
              id="remember"
              type="checkbox"
              class="w-4 h-4 border-slate-300 rounded text-blue-600 focus:ring-blue-500/20 transition-all cursor-pointer"
            />
            <label for="remember" class="ml-2 text-xs font-bold text-slate-500 cursor-pointer select-none">Ingat saya di perangkat ini</label>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full bg-blue-600 hover:bg-blue-500 disabled:bg-blue-600/50 text-white font-black text-xs uppercase tracking-widest py-4 rounded-xl shadow-lg shadow-blue-500/10 transition-all active:scale-[0.98] mt-2 cursor-pointer"
          >
            <span v-if="form.processing" class="flex items-center justify-center gap-2">
              <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Memproses...
            </span>
            <span v-else>Masuk</span>
          </button>
        </form>

        <div class="mt-10 pt-6 border-t border-slate-100 text-center">
          <p class="text-slate-500 text-xs font-bold">
            Belum memiliki akun?
            <Link href="/register" class="text-blue-600 font-black hover:text-blue-500 transition-colors ml-1 uppercase text-[10px] tracking-wider">Daftar Sekarang</Link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
