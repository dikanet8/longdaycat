<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const imagePreview = ref(null);

const form = useForm({
    kode_produk: '',
    nama_produk: '',
    gambar: null,
    ukuran: 'M',
    warna: '',
    harga: '',
    stok: '',
    stok_minimal: 5,
});

const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.gambar = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const submit = () => {
    form.post('/products', {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Tambah Produk" />

    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-8 relative z-10">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Tambah Produk Baru</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">Masukkan informasi produk untuk ditambahkan ke inventaris.</p>
                </div>
                <Link href="/products" class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center gap-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali
                </Link>
            </div>

            <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md rounded-3xl p-8 shadow-sm dark:shadow-2xl">
                <form @submit.prevent="submit" class="space-y-8">
                    
                    <!-- IMAGE UPLOAD (AT THE TOP) -->
                    <div class="flex flex-col items-center justify-center p-8 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-3xl bg-slate-50 dark:bg-white/5 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-all group relative">
                        <div v-if="imagePreview" class="relative w-48 h-48 mb-4">
                            <img :src="imagePreview" class="w-full h-full object-cover rounded-2xl shadow-2xl border-2 border-blue-500/20" />
                            <button @click="imagePreview = null; form.gambar = null" type="button" class="absolute -top-2 -right-2 bg-red-500 text-white p-1 rounded-full shadow-lg z-10">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div v-else class="text-center">
                            <div class="w-16 h-16 bg-blue-600/10 text-blue-600 dark:text-blue-400 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-slate-700 dark:text-white font-bold">Pilih Foto Produk</p>
                            <p class="text-slate-500 text-xs mt-1">PNG, JPG atau JPEG (Max. 2MB)</p>
                        </div>
                        <input 
                            type="file" 
                            @change="handleImageChange"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                            accept="image/*"
                        />
                        <div v-if="form.errors.gambar" class="text-red-400 text-xs mt-4 text-center font-bold">{{ form.errors.gambar }}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Kode Produk -->
                        <div class="space-y-2">
                            <label class="block text-sm font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Kode Produk</label>
                            <input 
                                v-model="form.kode_produk"
                                type="text" 
                                class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                                placeholder="Contoh: PRD001"
                                required
                            />
                            <div v-if="form.errors.kode_produk" class="text-red-400 text-xs mt-1">{{ form.errors.kode_produk }}</div>
                        </div>

                        <!-- Nama Produk -->
                        <div class="space-y-2">
                            <label class="block text-sm font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Nama Produk</label>
                            <input 
                                v-model="form.nama_produk"
                                type="text" 
                                class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                                placeholder="Masukkan nama produk"
                                required
                            />
                            <div v-if="form.errors.nama_produk" class="text-red-400 text-xs mt-1">{{ form.errors.nama_produk }}</div>
                        </div>

                        <!-- Harga -->
                        <div class="space-y-2">
                            <label class="block text-sm font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Harga (IDR)</label>
                            <input 
                                v-model="form.harga"
                                type="number" 
                                class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                                placeholder="0"
                                required
                            />
                            <div v-if="form.errors.harga" class="text-red-400 text-xs mt-1">{{ form.errors.harga }}</div>
                        </div>

                        <!-- Kategori (Ukuran & Warna) -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-sm font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Ukuran</label>
                                <select 
                                    v-model="form.ukuran"
                                    class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                                >
                                    <option value="S">S</option>
                                    <option value="M">M</option>
                                    <option value="L">L</option>
                                    <option value="XL">XL</option>
                                    <option value="XXL">XXL</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Warna</label>
                                <input 
                                    v-model="form.warna"
                                    type="text" 
                                    class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                                    placeholder="Contoh: Merah"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Stok & Stok Minimal -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-sm font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Stok Awal</label>
                                <input 
                                    v-model="form.stok"
                                    type="number" 
                                    class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                                    placeholder="0"
                                    required
                                />
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Stok Minimal</label>
                                <input 
                                    v-model="form.stok_minimal"
                                    type="number" 
                                    class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                                    placeholder="5"
                                    required
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4">
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-4 rounded-xl shadow-xl shadow-blue-500/20 transition-all active:scale-[0.98] disabled:opacity-50"
                        >
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Simpan Produk</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
