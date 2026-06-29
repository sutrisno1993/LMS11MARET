<template>
  <Head title="Ganti Tahun Ajaran" />

  <AppLayout
    title="Ganti Tahun Ajaran"
    subtitle="Reset database untuk memulai tahun ajaran baru"
  >
    <!-- Warning Banner -->
    <div class="mb-8 rounded-2xl border border-amber-500/30 bg-amber-500/5 p-6">
      <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-500/20 flex items-center justify-center text-2xl flex-shrink-0">
          ⚠️
        </div>
        <div>
          <h3 class="text-base font-bold text-amber-400 mb-1">Perhatian — Aksi Destruktif</h3>
          <p class="text-sm text-slate-400 leading-relaxed">
            Proses ini akan <strong class="text-white">menghapus seluruh data</strong> di database kecuali <strong class="text-amber-300">Jadwal JP</strong> dan <strong class="text-amber-300">Akun Admin</strong>.
            Setelah proses selesai, Anda harus mengimpor ulang data master (guru, kelas, mapel, siswa) dari SITAB.
          </p>
        </div>
      </div>
    </div>

    <!-- Stats Grid -->
    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-4">📊 Data Yang Akan Dihapus</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 mb-8">
      <div
        v-for="item in statItems"
        :key="item.label"
        class="rounded-2xl border border-white/8 p-5 group hover:border-rose-500/30 transition-colors"
        style="background: var(--card)"
      >
        <div class="flex items-center gap-3 mb-3">
          <div class="w-9 h-9 rounded-lg flex items-center justify-center text-lg"
               :class="item.bgClass">
            {{ item.icon }}
          </div>
        </div>
        <div class="text-2xl font-black tracking-tight text-white mb-0.5">
          {{ item.value.toLocaleString('id-ID') }}
        </div>
        <div class="text-[11px] text-slate-500 font-medium">{{ item.label }}</div>
      </div>
    </div>

    <!-- Preserved Data Info -->
    <div class="mb-8 rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-5">
      <div class="flex items-center gap-3 mb-2">
        <span class="text-lg">🛡️</span>
        <h3 class="text-sm font-bold text-emerald-400">Data Yang Dipertahankan</h3>
      </div>
      <div class="flex flex-wrap gap-3">
        <span class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold px-3 py-1.5 rounded-lg">
          ⏰ Jadwal JP (jp_schedules)
        </span>
        <span class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold px-3 py-1.5 rounded-lg">
          🔐 Akun Admin
        </span>
      </div>
    </div>

    <!-- Execute Button -->
    <div class="flex justify-center">
      <button
        @click="confirmExecute"
        :disabled="isProcessing"
        class="group relative px-8 py-4 bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 text-white font-bold rounded-2xl transition-all shadow-lg shadow-rose-500/20 hover:shadow-rose-500/40 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-3 text-base"
      >
        <span class="text-xl">{{ isProcessing ? '⏳' : '🔄' }}</span>
        {{ isProcessing ? 'Sedang Memproses...' : 'Mulai Ganti Tahun Ajaran' }}
      </button>
    </div>

    <!-- Confirm Modal -->
    <teleport to="body">
      <transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="showModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
          <!-- Backdrop -->
          <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showModal = false"></div>

          <!-- Modal Content -->
          <div class="relative bg-[#1E293B] border border-white/10 rounded-2xl shadow-2xl w-full max-w-md p-7 z-10">
            <div class="text-center mb-6">
              <div class="w-16 h-16 rounded-2xl bg-rose-500/20 flex items-center justify-center text-3xl mx-auto mb-4">
                🔒
              </div>
              <h3 class="text-xl font-black text-white mb-2">Konfirmasi Keamanan</h3>
              <p class="text-sm text-slate-400 leading-relaxed">
                Untuk keamanan, silakan masukkan password admin Anda **2 kali** untuk memverifikasi tindakan pembersihan data ini.
              </p>
            </div>

            <!-- Password Inputs -->
            <div class="space-y-4 mb-6">
              <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Password Admin</label>
                <input
                  v-model="password"
                  type="password"
                  placeholder="Masukkan password admin"
                  class="w-full bg-black/20 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:ring-1 focus:ring-rose-500 focus:border-rose-500 transition-all placeholder-slate-600"
                />
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Konfirmasi Password Admin</label>
                <input
                  v-model="passwordConfirmation"
                  type="password"
                  placeholder="Masukkan kembali password admin"
                  class="w-full bg-black/20 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:ring-1 focus:ring-rose-500 focus:border-rose-500 transition-all placeholder-slate-600"
                />
              </div>
              
              <!-- Validation Error -->
              <p v-if="validationError" class="text-xs text-rose-400 font-medium flex items-center gap-1.5">
                <span>⚠️</span> {{ validationError }}
              </p>
            </div>

            <div class="flex gap-3">
              <button
                @click="showModal = false"
                class="flex-1 px-4 py-3 bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 font-bold rounded-xl transition-colors text-sm"
              >
                Batalkan
              </button>
              <button
                @click="executeReset"
                :disabled="isProcessing || !password || !passwordConfirmation"
                class="flex-1 px-4 py-3 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl transition-colors text-sm disabled:opacity-50 flex items-center justify-center gap-2"
              >
                <span>{{ isProcessing ? '⏳' : '✓' }}</span>
                {{ isProcessing ? 'Memproses...' : 'Ya, Reset Sekarang' }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  stats: Object,
});

const showModal = ref(false);
const isProcessing = ref(false);
const password = ref('');
const passwordConfirmation = ref('');
const validationError = ref('');

const statItems = computed(() => [
  { icon: '👥', label: 'Siswa', value: props.stats.total_siswa, bgClass: 'bg-blue-500/20' },
  { icon: '👨‍🏫', label: 'Guru', value: props.stats.total_guru, bgClass: 'bg-violet-500/20' },
  { icon: '🏫', label: 'Kelas', value: props.stats.total_kelas, bgClass: 'bg-indigo-500/20' },
  { icon: '📚', label: 'Mata Pelajaran', value: props.stats.total_mapel, bgClass: 'bg-cyan-500/20' },
  { icon: '📋', label: 'Sesi KBM', value: props.stats.total_kbm, bgClass: 'bg-emerald-500/20' },
  { icon: '✅', label: 'Absensi Siswa', value: props.stats.total_absensi, bgClass: 'bg-green-500/20' },
  { icon: '📊', label: 'Nilai Siswa', value: props.stats.total_nilai, bgClass: 'bg-amber-500/20' },
  { icon: '📑', label: 'Rapor', value: props.stats.total_rapor, bgClass: 'bg-orange-500/20' },
  { icon: '📝', label: 'Ujian Live', value: props.stats.total_ujian, bgClass: 'bg-pink-500/20' },
  { icon: '✍️', label: 'Jawaban Ujian', value: props.stats.total_jawaban, bgClass: 'bg-rose-500/20' },
  { icon: '⭐', label: 'Respon Kuesioner', value: props.stats.total_kuesioner, bgClass: 'bg-yellow-500/20' },
  { icon: '⚡', label: 'Catatan Disiplin', value: props.stats.total_disiplin, bgClass: 'bg-red-500/20' },
  { icon: '📁', label: 'Materi', value: props.stats.total_materi, bgClass: 'bg-teal-500/20' },
  { icon: '🔑', label: 'Akun Non-Admin', value: props.stats.total_user_non_admin, bgClass: 'bg-slate-500/20' },
]);

const totalDataToDelete = computed(() => {
  return Object.values(props.stats).reduce((sum, val) => sum + val, 0);
});

const confirmExecute = () => {
  password.value = '';
  passwordConfirmation.value = '';
  validationError.value = '';
  showModal.value = true;
};

const executeReset = () => {
  validationError.value = '';
  
  if (password.value !== passwordConfirmation.value) {
    validationError.value = 'Password dan konfirmasi password tidak cocok!';
    return;
  }

  isProcessing.value = true;
  router.post('/admin/tahun-ajaran/execute', {
    password: password.value
  }, {
    onFinish: () => {
      isProcessing.value = false;
      showModal.value = false;
    },
  });
};
</script>
