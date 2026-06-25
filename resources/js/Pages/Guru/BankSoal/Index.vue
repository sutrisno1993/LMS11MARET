<template>
  <Head title="Bank Soal" />

  <AppLayout
    title="Bank Soal & Asesmen"
    subtitle="Kelola paket soal dan kuis untuk mata pelajaran Anda"
    :navigation="navigation"
  >
    <div class="space-y-6">
      
      <!-- Top Actions -->
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-white">Koleksi Bank Soal Anda</h2>
        <a href="/guru/bank-soal/create" class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors shadow-lg shadow-indigo-500/20">
          <span>➕</span> Buat Paket Soal Baru
        </a>
      </div>

      <!-- List Bank Soal -->
      <div v-if="banks.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="bank in banks" :key="bank.id_bank" class="rounded-2xl border border-white/10 p-5 hover:border-indigo-500/50 transition-all cursor-pointer group" style="background: var(--card)">
          <div class="flex items-start justify-between mb-4">
            <div class="px-3 py-1 bg-white/5 rounded-lg text-xs font-semibold text-slate-300">
              {{ bank.subject?.nama_mapel || 'Mapel Umum' }}
            </div>
            <div class="w-8 h-8 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 group-hover:scale-110 transition-transform">
              📝
            </div>
          </div>
          
          <h3 class="text-base font-bold text-white mb-2 line-clamp-2">{{ bank.judul }}</h3>
          <p class="text-xs text-slate-400 mb-4 line-clamp-2">{{ bank.deskripsi || 'Tidak ada deskripsi' }}</p>
          
          <div class="flex items-center justify-between mt-auto pt-4 border-t border-white/5">
            <span class="text-xs font-bold text-indigo-400">{{ bank.questions_count }} Butir Soal</span>
            <span class="text-xs text-slate-500">{{ formatDate(bank.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="rounded-2xl border border-white/10 p-10 text-center flex flex-col items-center justify-center" style="background: var(--card)">
        <div class="text-5xl mb-4">🗂️</div>
        <h3 class="text-lg font-bold text-white mb-2">Belum Ada Bank Soal</h3>
        <p class="text-sm text-slate-400 max-w-md mb-6">Anda belum membuat paket soal. Buat soal pilihan ganda sekarang untuk digunakan pada ujian live di kelas.</p>
        <a href="/guru/bank-soal/create" class="px-6 py-3 rounded-xl text-sm font-bold text-indigo-400 bg-indigo-500/10 hover:bg-indigo-500/20 transition-colors">
          Buat Bank Soal Pertama
        </a>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  banks: Array
});

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

const formatDate = (dateString) => {
  const d = new Date(dateString);
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>
