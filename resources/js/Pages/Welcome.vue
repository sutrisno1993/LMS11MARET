<template>
  <Head :title="`Selamat Datang - ${$page.props.app.branch.name}`" />

  <div class="min-h-screen flex items-center justify-center relative overflow-hidden transition-all duration-500"
       :style="isBekasi ? 'background: linear-gradient(135deg, #051D14 0%, #0D4730 50%, #062217 100%)' : 'background: linear-gradient(135deg, #0F0C29 0%, #302B63 50%, #24243e 100%)'">

    <!-- Animated background orbs -->
    <div class="absolute w-96 h-96 rounded-full opacity-30 blur-3xl animate-pulse transition-all duration-500"
         :style="isBekasi ? 'background:#10B981; top:-100px; left:-100px;' : 'background:#4F46E5; top:-100px; left:-100px;'"></div>
    <div class="absolute w-72 h-72 rounded-full opacity-30 blur-3xl transition-all duration-500"
         :style="isBekasi ? 'background:#0D9488; bottom:-80px; right:-80px; animation: pulse 4s ease-in-out infinite alternate;' : 'background:#7C3AED; bottom:-80px; right:-80px; animation: pulse 4s ease-in-out infinite alternate;'"></div>
    <div class="absolute w-52 h-52 rounded-full opacity-20 blur-3xl animate-bounce transition-all duration-500"
         :style="isBekasi ? 'background:#84CC16; bottom:100px; left:200px; animation-duration: 6s;' : 'background:#2563EB; bottom:100px; left:200px; animation-duration: 6s;'"></div>

    <!-- Grid overlay -->
    <div class="absolute inset-0 opacity-5"
         style="background-image: linear-gradient(rgba(255,255,255,0.5) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.5) 1px, transparent 1px); background-size: 50px 50px;"></div>

    <!-- Portal Card -->
    <div class="relative z-10 w-full max-w-md px-4">
      <div class="glass-card relative p-10 shadow-2xl shadow-black/50 rounded-3xl animate-fade-in" style="background: rgba(255,255,255,0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.05);">

        <!-- Admin Hidden Login -->
        <Link href="/login/admin" class="absolute top-6 right-6 text-slate-500 hover:text-white transition-colors text-lg opacity-50 hover:opacity-100" title="Admin Login">
          ⚙️
        </Link>

        <!-- Logo -->
        <div class="text-center mb-9">
          <div class="w-18 h-18 mx-auto mb-4 rounded-2xl flex items-center justify-center text-4xl shadow-xl transition-all duration-500"
               :class="isBekasi ? 'bg-gradient-to-br from-emerald-500 to-teal-600 shadow-emerald-500/40' : 'bg-gradient-to-br from-indigo-500 to-violet-600 shadow-indigo-500/40'"
               style="width:80px;height:80px;">
            🎓
          </div>
          <h1 class="text-2xl font-bold text-white">LMS SMK 11 Maret</h1>
          <p class="text-sm text-slate-300 mt-2">Portal Pembelajaran Digital ({{ $page.props.app.branch.name }})</p>
        </div>

        <!-- Role Selector -->
        <div class="mb-5">
          <label class="block text-center text-xs font-semibold text-slate-500 uppercase tracking-wider mb-6">Pilih Jalur Masuk</label>
          <div class="grid grid-cols-2 gap-4">
            <Link
              href="/login/guru"
              :class="['flex flex-col items-center justify-center p-4 rounded-2xl border bg-white/5 border-white/10 text-slate-300 transition-all duration-300 hover:-translate-y-1',
                       isBekasi ? 'hover:border-emerald-500/50 hover:bg-emerald-500/10 hover:text-white' : 'hover:border-indigo-500/50 hover:bg-indigo-500/10 hover:text-white']"
            >
              <span class="text-3xl mb-2">📚</span>
              <span class="text-xs font-medium">Guru / Walas</span>
            </Link>
            
            <Link
              href="/login/siswa"
              :class="['flex flex-col items-center justify-center p-4 rounded-2xl border bg-white/5 border-white/10 text-slate-300 transition-all duration-300 hover:-translate-y-1',
                       isBekasi ? 'hover:border-emerald-500/50 hover:bg-emerald-500/10 hover:text-white' : 'hover:border-indigo-500/50 hover:bg-indigo-500/10 hover:text-white']"
            >
              <span class="text-3xl mb-2">🎒</span>
              <span class="text-xs font-medium">Siswa</span>
            </Link>
          </div>
        </div>

        <div class="mt-6 text-center">
          <button @click="openResetModal = true" class="text-xs text-slate-400 hover:text-white underline transition-colors">
            Lupa Password? Ajukan Reset
          </button>
        </div>

        <div class="mt-8 border-t border-white/8 pt-5 flex items-center justify-center gap-2 text-xs text-slate-600">
          <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
          SMK 11 Maret | 2026/2027
        </div>
      </div>
    </div>

    <!-- Modal Lupa Password -->
    <div v-if="openResetModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 animate-fade-in">
      <!-- Overlay Backdrop -->
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="openResetModal = false"></div>
      
      <!-- Modal Content -->
      <div class="relative z-10 w-full max-w-md glass-card p-8 rounded-3xl shadow-2xl transition-all duration-300"
           style="background: rgba(20, 20, 20, 0.85); backdrop-filter: blur(25px); border: 1px solid rgba(255, 255, 255, 0.08);">
        
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-lg font-bold text-white">Ajukan Reset Password</h2>
          <button @click="openResetModal = false" class="text-slate-400 hover:text-white text-xl">×</button>
        </div>

        <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-xs rounded-xl">
          {{ $page.props.flash.success }}
        </div>
        <div v-if="form.hasErrors" class="mb-4 p-4 bg-red-500/20 border border-red-500/30 text-red-300 text-xs rounded-xl">
          Terjadi kesalahan. Silakan periksa kembali inputan Anda.
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-2">Saya Adalah</label>
            <div class="grid grid-cols-2 gap-2">
              <button type="button" @click="form.role = 'STUDENT'"
                      :class="['py-2 px-3 rounded-xl border text-xs font-semibold transition-all duration-200', 
                               form.role === 'STUDENT' 
                               ? (isBekasi ? 'bg-emerald-500/20 border-emerald-500 text-white' : 'bg-indigo-500/20 border-indigo-500 text-white')
                               : 'bg-white/5 border-white/10 text-slate-400 hover:text-white']">
                🎒 Siswa
              </button>
              <button type="button" @click="form.role = 'TEACHER'"
                      :class="['py-2 px-3 rounded-xl border text-xs font-semibold transition-all duration-200', 
                               form.role === 'TEACHER' 
                               ? (isBekasi ? 'bg-emerald-500/20 border-emerald-500 text-white' : 'bg-indigo-500/20 border-indigo-500 text-white')
                               : 'bg-white/5 border-white/10 text-slate-400 hover:text-white']">
                📚 Guru / Walas
              </button>
            </div>
          </div>

          <div>
            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-2">
              {{ form.role === 'STUDENT' ? 'NISN atau NIS' : 'Nomor WhatsApp' }}
            </label>
            <input v-model="form.identifier" type="text" required
                   :placeholder="form.role === 'STUDENT' ? 'Masukkan NISN / NIS' : 'Contoh: 0812xxxxxx'"
                   :class="['w-full bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-xs text-white placeholder-slate-600 outline-none transition-all duration-300',
                            isBekasi ? 'focus:border-emerald-500 focus:bg-emerald-500/10 focus:ring-2 focus:ring-emerald-500/20' : 'focus:border-indigo-500 focus:bg-indigo-500/10 focus:ring-2 focus:ring-indigo-500/20']" />
            <p v-if="form.errors.identifier" class="text-red-400 text-[11px] mt-1">{{ form.errors.identifier }}</p>
          </div>

          <div>
            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-2">Password Baru</label>
            <input v-model="form.new_password" type="password" required placeholder="Minimal 6 karakter"
                   :class="['w-full bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-xs text-white placeholder-slate-600 outline-none transition-all duration-300',
                            isBekasi ? 'focus:border-emerald-500 focus:bg-emerald-500/10 focus:ring-2 focus:ring-emerald-500/20' : 'focus:border-indigo-500 focus:bg-indigo-500/10 focus:ring-2 focus:ring-indigo-500/20']" />
            <p v-if="form.errors.new_password" class="text-red-400 text-[11px] mt-1">{{ form.errors.new_password }}</p>
          </div>

          <div>
            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-2">Konfirmasi Password Baru</label>
            <input v-model="form.new_password_confirmation" type="password" required placeholder="Ulangi password baru"
                   :class="['w-full bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-xs text-white placeholder-slate-600 outline-none transition-all duration-300',
                            isBekasi ? 'focus:border-emerald-500 focus:bg-emerald-500/10 focus:ring-2 focus:ring-emerald-500/20' : 'focus:border-indigo-500 focus:bg-indigo-500/10 focus:ring-2 focus:ring-indigo-500/20']" />
          </div>

          <button type="submit" :disabled="form.processing"
                  class="w-full py-3.5 rounded-xl text-xs font-semibold text-white transition-all duration-300 hover:-translate-y-0.5 disabled:opacity-60 disabled:cursor-not-allowed mt-4"
                  :style="isBekasi 
                    ? 'background: linear-gradient(135deg, #10B981, #059669); box-shadow: 0 8px 20px rgba(16,185,129,0.3);' 
                    : 'background: linear-gradient(135deg, #4F46E5, #7C3AED); box-shadow: 0 8px 20px rgba(79,70,229,0.3);'">
            <span v-if="!form.processing">Kirim Pengajuan →</span>
            <span v-else>Memproses...</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const page = usePage();
const isBekasi = computed(() => page.props.app.branch.code === 'BKS');

const openResetModal = ref(false);

const form = useForm({
  role: 'STUDENT',
  identifier: '',
  new_password: '',
  new_password_confirmation: '',
});

const submit = () => {
  form.post('/password/reset-request', {
    onSuccess: () => {
      form.reset('identifier', 'new_password', 'new_password_confirmation');
    }
  });
};
</script>
