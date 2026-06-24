<template>
  <Head title="Laporan Kuesioner" />

  <AppLayout
    :title="`Laporan: ${questionnaire.judul}`"
    subtitle="Rekapitulasi hasil penilaian siswa terhadap kinerja Pendidik"
    :navigation="navigation"
  >
    <div class="space-y-6">
      
      <div class="flex items-center gap-4 mb-8">
        <a href="/admin/kuesioner" class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center text-white hover:bg-white/10 transition-colors">
          ←
        </a>
        <div>
          <div class="text-xs font-bold text-slate-400">Total Pertanyaan: {{ questionnaire.questions.length }}</div>
          <div class="text-xs font-bold" :class="questionnaire.is_active ? 'text-green-400' : 'text-slate-500'">
            Status: {{ questionnaire.is_active ? 'SEDANG BERJALAN' : 'DITUTUP' }}
          </div>
        </div>
      </div>

      <!-- Rekap Guru -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="guru in rekapGuru" :key="guru.id_guru" 
             class="rounded-2xl border border-white/10 p-5 space-y-4" style="background: var(--card)">
          
          <div class="flex gap-4 items-center border-b border-white/5 pb-4">
            <div class="w-12 h-12 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-lg">
              {{ guru.nama_guru.charAt(0) }}
            </div>
            <div>
              <div class="font-bold text-white text-sm line-clamp-1">{{ guru.nama_guru }}</div>
              <div class="text-[10px] text-slate-400 mt-1">Dinilai oleh {{ guru.total_respon }} siswa</div>
            </div>
          </div>

          <div class="flex items-end justify-between">
            <div class="text-xs font-bold text-slate-400">Rating Rata-Rata</div>
            <div class="flex items-center gap-1">
              <span class="text-yellow-400 text-lg">★</span>
              <span class="text-2xl font-black text-white">{{ guru.avg_rating }}</span>
              <span class="text-xs text-slate-500 mb-1">/5.0</span>
            </div>
          </div>

          <button @click="openComments(guru)" class="w-full py-2 rounded-lg bg-white/5 hover:bg-white/10 text-xs font-semibold text-indigo-400 transition-colors border border-white/5">
            Lihat {{ guru.komentar.length }} Komentar
          </button>
        </div>
      </div>

      <div v-if="rekapGuru.length === 0" class="text-center p-10 border border-white/10 rounded-2xl">
        <div class="text-4xl mb-4">📭</div>
        <div class="text-white font-bold mb-2">Belum Ada Data</div>
        <div class="text-sm text-slate-400">Belum ada satupun siswa yang mengisi kuesioner ini.</div>
      </div>

      <!-- Modal Comments -->
      <div v-if="selectedGuru" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div class="w-full max-w-2xl bg-[#0B0F19] border border-white/10 rounded-2xl p-0 shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
          
          <div class="p-5 border-b border-white/10 flex items-center justify-between bg-white/5">
            <div>
              <h3 class="font-bold text-white text-base">{{ selectedGuru.nama_guru }}</h3>
              <p class="text-xs text-slate-400">Daftar keluhan & saran dari siswa (Kurikulum memiliki akses identitas siswa)</p>
            </div>
            <button @click="selectedGuru = null" class="w-8 h-8 rounded-lg hover:bg-white/10 text-slate-400 flex items-center justify-center">✕</button>
          </div>

          <div class="p-5 overflow-y-auto flex-1 space-y-4">
            <div v-for="(c, i) in selectedGuru.komentar" :key="i" class="p-4 rounded-xl border border-white/5 bg-white/2">
              <div class="flex items-start justify-between mb-2">
                <div>
                  <span class="text-xs font-bold text-indigo-400 mr-2">{{ c.nama_siswa }}</span>
                  <span class="text-[10px] px-2 py-0.5 rounded bg-white/10 text-slate-300">{{ c.kelas }}</span>
                </div>
                <div class="flex text-yellow-400 text-[10px]">
                  <span v-for="star in 5" :key="star" :class="star <= c.rating ? 'opacity-100' : 'opacity-20'">★</span>
                </div>
              </div>
              <p class="text-sm text-slate-300 italic">"{{ c.komentar }}"</p>
            </div>

            <div v-if="selectedGuru.komentar.length === 0" class="text-center py-8 text-sm text-slate-500">
              Tidak ada siswa yang meninggalkan komentar tertulis.
            </div>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  questionnaire: Object,
  rekapGuru: Array
});

const selectedGuru = ref(null);

const openComments = (guru) => {
  selectedGuru.value = guru;
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
