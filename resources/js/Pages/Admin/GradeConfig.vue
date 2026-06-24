<template>
  <Head title="Konfigurasi Bobot Nilai" />

  <AppLayout
    title="Konfigurasi Bobot Nilai"
    subtitle="Atur persentase bobot Formatif dan Sumatif untuk kalkulasi Rapor secara global."
    :navigation="navigation"
  >
    <div class="max-w-2xl mx-auto space-y-6">
      
      <transition name="fade">
        <div v-if="$page.props.flash?.success" class="p-4 rounded-xl border border-green-500/20 bg-green-500/10 text-green-400 text-sm flex items-center gap-3">
          <span>✅</span> {{ $page.props.flash.success }}
        </div>
      </transition>
      
      <transition name="fade">
        <div v-if="$page.props.flash?.error" class="p-4 rounded-xl border border-red-500/20 bg-red-500/10 text-red-400 text-sm flex items-center gap-3">
          <span>⚠️</span> {{ $page.props.flash.error }}
        </div>
      </transition>

      <div class="rounded-2xl border border-white/10 p-6 bg-[#0B0F19]">
        <h3 class="font-bold text-white mb-2">Persentase Evaluasi Pembelajaran</h3>
        <p class="text-xs text-slate-400 mb-6">Geser slider di bawah ini untuk membagi rasio bobot antara Asesmen Formatif (Ulangan Harian, Tugas) dan Sumatif (PTS, PAS).</p>

        <form @submit.prevent="submit" class="space-y-8">
          
          <!-- Slider Container -->
          <div class="space-y-4">
            <div class="flex justify-between font-bold text-sm">
              <span class="text-indigo-400">Formatif: {{ form.bobot_formatif }}%</span>
              <span class="text-orange-400">Sumatif: {{ form.bobot_sumatif }}%</span>
            </div>
            
            <input 
              type="range" 
              min="0" 
              max="100" 
              step="5"
              v-model="form.bobot_formatif" 
              @input="updateSumatif"
              class="w-full h-2 bg-white/10 rounded-lg appearance-none cursor-pointer accent-indigo-500"
            >
            
            <div class="flex justify-between text-[10px] text-slate-500 font-medium">
              <span>0% (Hanya Ujian)</span>
              <span>100% (Tanpa Ujian)</span>
            </div>
          </div>

          <!-- Simulation Card -->
          <div class="p-4 rounded-xl border border-white/5 bg-white/5">
            <h4 class="text-xs font-bold text-slate-400 mb-3">Simulasi Kalkulasi:</h4>
            <div class="text-sm space-y-1 text-slate-300">
              <p>Jika nilai rata-rata Tugas Siswa = <strong>80</strong></p>
              <p>Jika nilai Ujian Akhir Siswa = <strong>90</strong></p>
              <p class="pt-2 mt-2 border-t border-white/10">
                Nilai Akhir Rapor = <strong class="text-green-400">{{ (80 * (form.bobot_formatif / 100)) + (90 * (form.bobot_sumatif / 100)) }}</strong>
              </p>
            </div>
          </div>

          <div class="flex justify-end">
            <button 
              type="submit" 
              :disabled="form.processing"
              class="px-6 py-2.5 rounded-xl font-bold text-sm bg-indigo-600 hover:bg-indigo-500 text-white transition-all disabled:opacity-50 flex items-center gap-2 shadow-lg shadow-indigo-500/20"
            >
              Simpan Konfigurasi
            </button>
          </div>
        </form>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  gradeConfig: Object
});

const form = useForm({
  bobot_formatif: props.gradeConfig?.bobot_formatif || 50,
  bobot_sumatif: props.gradeConfig?.bobot_sumatif || 50,
});

const updateSumatif = () => {
  form.bobot_sumatif = 100 - form.bobot_formatif;
};

const submit = () => {
  form.post('/admin/grade-config');
};

const navigation = [
  {
    label: 'Utama',
    items: [
      { href: '/admin/dashboard', icon: '📊', label: 'Dashboard' },
    ],
  },
  {
    label: 'Sekolah',
    items: [
      { href: '/admin/wali-kelas', icon: '👤', label: 'Wali Kelas' },
      { href: '/admin/siswa', icon: '👥', label: 'Data Siswa' },
      { href: '/admin/events', icon: '🗓️', label: 'Event & Libur' },
      { href: '/admin/kuesioner', icon: '⭐', label: 'Kuesioner Kinerja' },
    ],
  },
  {
    label: 'Konfigurasi',
    items: [
      { href: '/admin/jadwal-jp', icon: '⏰', label: 'Jadwal JP' },
      { href: '/admin/grade-config', icon: '⚖️', label: 'Bobot Nilai' },
      { href: '/admin/timeline', icon: '📅', label: 'Timeline' },
    ],
  },
  {
    label: 'Laporan',
    items: [
      { href: '/admin/monitoring-nilai', icon: '📈', label: 'Monitoring Nilai' },
      { href: '/admin/rapor', icon: '📋', label: 'Ekspor Rapor' },
      { href: '/admin/laporan-performa', icon: '🏆', label: 'Laporan Performa' },
      { href: '/admin/jurnal', icon: '📓', label: 'Jurnal Tatap Muka' },
    ],
  }
];
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
