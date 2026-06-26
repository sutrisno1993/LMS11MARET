<template>
  <Head title="Konfigurasi Bobot Nilai" />

  <AppLayout
    title="Konfigurasi Bobot Nilai"
    subtitle="Atur persentase bobot Formatif dan Sumatif untuk kalkulasi Rapor secara global."
    :navigation="navigation"
  >
    <div class="max-w-2xl mx-auto space-y-6">

      <div class="rounded-2xl border border-white/10 p-6 bg-[#0B0F19]">
        <h3 class="font-bold text-white mb-2">Persentase Evaluasi Pembelajaran</h3>
        <p class="text-xs text-slate-400 mb-6">Geser slider di bawah ini untuk membagi rasio bobot antara Asesmen Formatif (Ulangan Harian, Tugas) dan Sumatif (PTS, PAS).</p>

        <form @submit.prevent="submit" class="space-y-8">
          
          <!-- Inputs Container -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="space-y-2">
              <label class="block text-xs font-bold text-slate-400 uppercase">Bobot Formatif (%)</label>
              <input 
                type="number" 
                min="0" 
                max="100" 
                v-model.number="form.bobot_formatif" 
                class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-indigo-500 outline-none"
              >
            </div>
            
            <div class="space-y-2">
              <label class="block text-xs font-bold text-slate-400 uppercase">Bobot Sumatif (%)</label>
              <input 
                type="number" 
                min="0" 
                max="100" 
                v-model.number="form.bobot_sumatif" 
                class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-indigo-500 outline-none"
              >
            </div>

            <div class="space-y-2">
              <label class="block text-xs font-bold text-slate-400 uppercase">Bobot Absensi (%)</label>
              <input 
                type="number" 
                min="0" 
                max="100" 
                v-model.number="form.bobot_absensi" 
                class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-indigo-500 outline-none"
              >
            </div>
          </div>

          <!-- Total Counter -->
          <div class="p-4 rounded-xl border flex items-center justify-between"
               :class="totalBobot === 100 ? 'border-green-500/20 bg-green-500/5 text-green-400' : 'border-red-500/20 bg-red-500/5 text-red-400'">
            <span class="text-xs font-bold uppercase">Total Persentase Bobot:</span>
            <span class="text-lg font-black">{{ totalBobot }}% (Harus 100%)</span>
          </div>

          <!-- Simulation Card -->
          <div class="p-4 rounded-xl border border-white/5 bg-white/5">
            <h4 class="text-xs font-bold text-slate-400 mb-3">Simulasi Kalkulasi:</h4>
            <div class="text-sm space-y-1 text-slate-300">
              <p>Jika nilai rata-rata Tugas Siswa = <strong>80</strong></p>
              <p>Jika nilai Ujian Akhir Siswa = <strong>90</strong></p>
              <p>Jika tingkat kehadiran Siswa = <strong>95</strong></p>
              <p class="pt-2 mt-2 border-t border-white/10">
                Nilai Akhir Rapor = <strong class="text-green-400">
                  {{ Math.round((80 * (form.bobot_formatif / 100)) + (90 * (form.bobot_sumatif / 100)) + (95 * (form.bobot_absensi / 100))) }}
                </strong>
              </p>
            </div>
          </div>

          <div class="flex justify-end">
            <button 
              type="submit" 
              :disabled="form.processing || totalBobot !== 100"
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
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  gradeConfig: Object
});

const page = usePage();

const form = useForm({
  bobot_formatif: props.gradeConfig?.bobot_formatif ?? 40,
  bobot_sumatif: props.gradeConfig?.bobot_sumatif ?? 40,
  bobot_absensi: props.gradeConfig?.bobot_absensi ?? 20,
});

const totalBobot = computed(() => {
  return Number(form.bobot_formatif) + Number(form.bobot_sumatif) + Number(form.bobot_absensi);
});

const submit = () => {
  if (totalBobot.value !== 100) {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Total bobot harus tepat 100%!', type: 'error' } }));
    return;
  }
  form.post('/admin/grade-config', {
    preserveScroll: true,
  });
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
