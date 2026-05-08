<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register" />

    <div class="min-h-screen bg-[#0f172a] flex items-center justify-center p-6 font-sans">
        <!-- Decorative blobs -->
        <div class="absolute top-0 left-0 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl translate-x-1/2 translate-y-1/2"></div>

        <div class="relative w-full max-w-md bg-white/5 backdrop-blur-xl border border-white/10 p-8 rounded-3xl shadow-2xl overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-purple-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

            <div class="relative">
                <div class="mb-8 text-center">
                    <h1 class="text-4xl font-bold text-white mb-2 tracking-tight">Create Account</h1>
                    <p class="text-slate-400">Join us and start your journey today</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Full Name</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all"
                            placeholder="John Doe"
                            required
                        />
                        <div v-if="form.errors.name" class="text-red-400 text-sm mt-1 font-medium">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all"
                            placeholder="name@example.com"
                            required
                        />
                        <div v-if="form.errors.email" class="text-red-400 text-sm mt-1 font-medium">{{ form.errors.email }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Password</label>
                            <input
                                v-model="form.password"
                                type="password"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all"
                                placeholder="••••••••"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Confirm</label>
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all"
                                placeholder="••••••••"
                                required
                            />
                        </div>
                        <div v-if="form.errors.password" class="col-span-2 text-red-400 text-sm mt-1 font-medium">{{ form.errors.password }}</div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-white text-[#0f172a] hover:bg-slate-200 disabled:bg-slate-400 font-bold py-3 rounded-xl shadow-lg transition-all active:scale-[0.98] mt-4"
                    >
                        <span v-if="form.processing" class="flex items-center justify-center">
                            <svg class="animate-spin h-5 w-5 mr-3 text-slate-900" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Creating account...
                        </span>
                        <span v-else>Register Now</span>
                    </button>
                </form>

                <div class="mt-8 pt-8 border-t border-white/5 text-center">
                    <p class="text-slate-400 text-sm">
                        Already have an account?
                        <Link href="/login" class="text-white font-semibold hover:text-blue-400 transition-colors ml-1">Sign in instead</Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
