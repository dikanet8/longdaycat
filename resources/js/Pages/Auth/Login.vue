<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Login" />

    <div class="min-h-screen bg-[#0f172a] flex items-center justify-center p-4 sm:p-6 font-sans relative overflow-hidden">
        <!-- Decorative blobs -->
        <div class="absolute top-0 left-0 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl translate-x-1/2 translate-y-1/2"></div>

        <div class="relative w-full max-w-4xl bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2 group min-h-[550px]">
            <!-- Glow effect on hover -->
            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-purple-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

            <!-- Left Column: Image (Hidden on Mobile) -->
            <div class="hidden md:block relative overflow-hidden">
                <img 
                    src="https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&q=80&w=800" 
                    alt="Aesthetic Cat" 
                    class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                />
                <!-- Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/40 to-transparent"></div>
                
                <!-- Overlay Content -->
                <div class="absolute inset-0 p-12 flex flex-col justify-between z-10">
                    <!-- Brand -->
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center shadow-lg shadow-blue-500/25">
                            <span class="text-white font-bold italic text-xl">L</span>
                        </div>
                        <span class="font-bold text-xl tracking-tight text-white">Longdaycat.Co</span>
                    </div>

                    <!-- Quote/Caption -->
                    <div class="space-y-4">
                        <h2 class="text-3xl font-extrabold text-white leading-tight tracking-tight">
                            Kelola Petshop Anda dengan Lebih Cerdas.
                        </h2>
                        <p class="text-slate-300 text-sm leading-relaxed font-medium">
                            Aplikasi Point of Sales (POS) dan pengelolaan stok produk hewan peliharaan yang modern, cepat, dan efisien.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right Column: Login Form -->
            <div class="p-8 sm:p-12 flex flex-col justify-center relative">
                <div>
                    <div class="mb-8">
                        <!-- Mobile Brand Logo (Visible on mobile only) -->
                        <div class="flex items-center gap-2 mb-6 md:hidden">
                            <div class="w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center">
                                <span class="text-white font-bold italic text-base">L</span>
                            </div>
                            <span class="font-bold text-lg text-white">Longdaycat.Co</span>
                        </div>
                        
                        <h1 class="text-3xl font-black text-white mb-2 tracking-tight">Selamat Datang</h1>
                        <p class="text-slate-400 text-xs sm:text-sm font-medium">Masuk ke akun Anda untuk mulai mengelola transaksi toko</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-2">Alamat Email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 text-sm transition-all"
                                placeholder="nama@email.com"
                                required
                            />
                            <div v-if="form.errors.email" class="text-red-400 text-xs mt-1.5 font-bold">{{ form.errors.email }}</div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-black uppercase tracking-wider text-slate-400">Kata Sandi</label>
                                <a href="#" class="text-[11px] font-bold text-blue-400 hover:text-blue-300 transition-colors">Lupa Kata Sandi?</a>
                            </div>
                            <input
                                v-model="form.password"
                                type="password"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 text-sm transition-all"
                                placeholder="••••••••"
                                required
                            />
                            <div v-if="form.errors.password" class="text-red-400 text-xs mt-1.5 font-bold">{{ form.errors.password }}</div>
                        </div>

                        <div class="flex items-center">
                            <input
                                v-model="form.remember"
                                id="remember"
                                type="checkbox"
                                class="w-4 h-4 bg-white/5 border-white/10 rounded text-blue-500 focus:ring-blue-500/50 focus:ring-offset-0 transition-all cursor-pointer"
                            />
                            <label for="remember" class="ml-2 text-xs font-bold text-slate-400 cursor-pointer select-none">Ingat saya di perangkat ini</label>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full bg-blue-600 hover:bg-blue-500 disabled:bg-blue-600/50 text-white font-black text-xs uppercase tracking-widest py-4 rounded-xl shadow-lg shadow-blue-500/25 transition-all active:scale-[0.98] mt-2 cursor-pointer"
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

                    <div class="mt-8 pt-6 border-t border-white/5 text-center">
                        <p class="text-slate-400 text-xs font-bold">
                            Belum memiliki akun?
                            <Link href="/register" class="text-white font-black hover:text-blue-400 transition-colors ml-1 uppercase text-[10px] tracking-wider">Daftar Sekarang</Link>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
