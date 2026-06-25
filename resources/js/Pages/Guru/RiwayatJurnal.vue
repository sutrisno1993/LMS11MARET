<template>
  <Head title="Riwayat Jurnal Mengajar" />

  <AppLayout
    title="Riwayat Jurnal Mengajar"
    subtitle="Riwayat tatap muka harian dan presensi KBM yang telah Anda laksanakan"
    :navigation="navigation"
  >
    <!-- HISTORY LIST -->
    <div class="space-y-5">
      <div 
        v-for="session in sessions" 
        :key="session.id"
        class="rounded-2xl border border-white/8 overflow-hidden transition-all duration-200 hover:border-white/15"
        style="background: var(--card)"
      >
        <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-5">
          
          <!-- LEFT: Mapel, Kelas & Waktu -->
          <div class="space-y-2 md:max-w-xs flex-1">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-xs font-bold px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-400">
                Jam ke-{{ session.jam_ke }}
              </span>
              <span class="text-xs font-bold px-2 py-0.5 rounded bg-white/5 text-slate-300">
                🏫 {{ session.kelas }}
              </span>
              <span class="text-[10px] text-slate-500 font-mono">
                📅 {{ formatDate(session.tanggal) }}
              </span>
            </div>
            <h3 class="text-base font-black text-white leading-tight">
              {{ session.mapel }}
            </h3>
          </div>

          <!-- MIDDLE: Ringkasan Materi (Jurnal) -->
          <div class="flex-1 md:max-w-xl bg-white/2 rounded-xl p-3 border border-white/4">
            <span class="block text-[9px] uppercase font-bold text-slate-500 tracking-wider mb-1">Materi Log / Jurnal</span>
            <p class="text-xs text-slate-300 italic line-clamp-3">
              "{{ session.materi_log || '-' }}"
            </p>
          </div>

          <!-- RIGHT: Absensi Statistik Summary -->
          <div class="flex items-center gap-4 justify-between md:justify-end min-w-[150px]">
            <div class="text-right space-y-1">
              <div class="text-[10px] text-slate-500 uppercase font-semibold">Kehadiran Siswa:</div>
              <div class="text-sm font-bold text-white">
                <span class="text-green-400">{{ session.stats.hadir }}</span> / {{ session.stats.total }} Hadir
              </div>
              <div class="flex gap-1.5 text-[9px] font-mono text-slate-500 justify-end">
                <span v-if="session.stats.sakit > 0" class="text-amber-500">S:{{ session.stats.sakit }}</span>
                <span v-if="session.stats.izin > 0" class="text-blue-500">I:{{ session.stats.izin }}</span>
                <span v-if="session.stats.alpa > 0" class="text-red-500">A:{{ session.stats.alpa }}</span>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Empty state -->
      <div v-if="sessions.length === 0" class="rounded-2xl border border-white/8 p-12 text-center text-slate-500" style="background: var(--card)">
        <div class="text-4xl mb-3">📜</div>
        <div class="font-bold text-white">Tidak Ada Riwayat Mengajar</div>
        <div class="text-xs mt-1 text-slate-600">Anda belum pernah menyelesaikan sesi KBM apa pun.</div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  sessions: Array
});

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
};

// Guru Sidebar Navigation
const navigation = [
  {
    label: 'KBM (Kegiatan Belajar Mengajar)',
    items: [
      { href: '/guru/dashboard', icon: '📊', label: 'Dashboard' },
      { href: '/guru/jadwal', icon: '📅', label: 'Jadwal Mengajar' },
      { href: '/guru/riwayat-jurnal', icon: '📜', label: 'Riwayat Jurnal Mengajar' },
      { href: '/guru/materi', icon: '📁', label: 'Materi Pembelajaran' },
    ],
  },
  {
    label: 'Evaluasi & Penilaian',
    items: [
      { href: '/guru/pemetaan-materi', icon: '🗺️', label: 'Pemetaan Materi' },
      { href: '/guru/bank-soal', icon: '📝', label: 'Bank Soal & Ujian Live' },
      { href: '/guru/nilai-sumatif', icon: '📊', label: 'Nilai Sumatif' },
      { href: '/guru/nilai-akhir', icon: '📋', label: 'Nilai Akhir' },
      { href: '/guru/rapor-preview', icon: '📑', label: 'Rapor Preview' },
    ],
  },
];
</script>

<style scoped>
</style>
