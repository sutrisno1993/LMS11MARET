<template>
  <Head title="Kalkulasi Nilai Akhir" />

  <AppLayout
    title="Nilai Akhir & SAS"
    subtitle="Kalkulasi nilai rata-rata TP dan nilai Sumatif Akhir Semester (SAS) menjadi Nilai Rapor"
    :navigation="navigation"
  >
    <template #topbar-actions>
      <div class="flex items-center gap-2 mr-4">
        <label class="text-xs text-slate-500 font-semibold uppercase">Pilih Kelas:</label>
        <select v-model="selectedKelas" class="bg-white/5 border border-white/10 rounded-lg px-3 py-1.5 text-sm text-white outline-none focus:border-indigo-500">
          <option value="1">XII TKJ 1</option>
          <option value="2">XII TKJ 2</option>
        </select>
      </div>
      <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-semibold text-white transition-colors shadow-lg shadow-indigo-500/30 flex items-center gap-2">
        <span>⚙️</span> Sinkronisasi ke Rapor
      </button>
    </template>

    <div class="space-y-6">
      
      <!-- Top Stats -->
      <div class="grid grid-cols-4 gap-4">
        <div class="rounded-2xl border border-white/8 p-4 bg-white/2">
          <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Bobot Nilai Saat Ini</div>
          <div class="mt-2 flex items-center gap-3">
            <div class="flex-1 text-center bg-indigo-500/10 border border-indigo-500/20 rounded-lg p-2">
              <div class="text-lg font-bold text-indigo-400">70%</div>
              <div class="text-[9px] text-slate-400 uppercase">Rata-rata TP</div>
            </div>
            <div class="text-slate-600 font-bold">+</div>
            <div class="flex-1 text-center bg-green-500/10 border border-green-500/20 rounded-lg p-2">
              <div class="text-lg font-bold text-green-400">30%</div>
              <div class="text-[9px] text-slate-400 uppercase">Ujian SAS</div>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-white/8 p-4 bg-white/2">
          <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Status Kelas</div>
          <div class="mt-2 text-2xl font-black text-white">36 <span class="text-sm font-normal text-slate-400">Siswa</span></div>
          <div class="text-xs text-green-400 mt-1">✓ Semua nilai TP lengkap</div>
        </div>

        <div class="rounded-2xl border border-white/8 p-4 bg-white/2">
          <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Rata-rata Kelas (Rapor)</div>
          <div class="mt-2 text-2xl font-black text-white">83.4</div>
          <div class="text-xs text-slate-400 mt-1">Tertinggi: 95 | Terendah: 68</div>
        </div>
      </div>

      <!-- Main Table -->
      <div class="rounded-2xl border border-white/8 overflow-hidden" style="background: var(--card)">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-white/4 border-b border-white/8">
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-r border-white/8 w-12 text-center">No</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-r border-white/8">Nama Siswa</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-r border-white/8 text-center w-32" title="Rata-rata dari nilai grid Sumatif TP">
                  Rata-rata TP<br/><span class="text-indigo-400">(70%)</span>
                </th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-r border-white/8 text-center w-32 bg-green-500/5">
                  Nilai SAS<br/><span class="text-green-400">(30%)</span>
                </th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-r border-white/8 text-center w-32 bg-indigo-500/10">
                  NILAI RAPOR<br/>(Akhir)
                </th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(siswa, idx) in students" :key="siswa.id" class="border-b border-white/4 hover:bg-white/2 transition-colors">
                <td class="px-4 py-3 text-sm text-slate-500 text-center border-r border-white/8">{{ idx + 1 }}</td>
                <td class="px-4 py-3 border-r border-white/8">
                  <div class="text-sm font-semibold text-white">{{ siswa.nama }}</div>
                  <div class="text-[10px] text-slate-500">{{ siswa.nis }}</div>
                </td>
                
                <!-- Rata-rata TP (Readonly, calculated from previous page) -->
                <td class="px-4 py-3 text-center border-r border-white/8 font-mono text-sm" :class="siswa.rataTP < 70 ? 'text-red-400' : 'text-slate-300'">
                  {{ siswa.rataTP }}
                </td>

                <!-- Nilai SAS (Inputable) -->
                <td class="p-0 border-r border-white/8 bg-green-500/5 relative">
                  <input
                    v-model.number="siswa.sas"
                    type="number" min="0" max="100"
                    class="w-full h-full absolute inset-0 bg-transparent text-center text-sm font-bold text-green-400 outline-none focus:bg-green-500/20 transition-all"
                  />
                  <div class="h-12"></div>
                </td>

                <!-- Calculated Rapor -->
                <td class="px-4 py-3 text-center border-r border-white/8 bg-indigo-500/10 font-black text-lg" :class="kalkulasiRapor(siswa) < 70 ? 'text-red-400' : 'text-white'">
                  {{ kalkulasiRapor(siswa) }}
                </td>

                <!-- Action -->
                <td class="px-4 py-3">
                  <a :href="`/guru/rapor-preview?siswa_id=${siswa.id}`" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 bg-indigo-500/10 px-3 py-1.5 rounded-lg border border-indigo-500/20 transition-colors">
                    📝 Edit Deskripsi
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const navigation = [
  {
    label: 'KBM (Kegiatan Belajar Mengajar)',
    items: [
      { href: '/guru/dashboard', icon: '📊', label: 'Dashboard' },
      { href: '/guru/riwayat-jurnal', icon: '📜', label: 'Riwayat Jurnal Mengajar' },
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

const selectedKelas = ref("1");

// Bobot (Bisa diambil dari setting admin)
const bobotTP = 0.7;
const bobotSAS = 0.3;

const students = ref([
  { id: 1, nama: 'Ahmad Rifai', nis: '2024001', rataTP: 83, sas: 85 },
  { id: 2, nama: 'Budi Santoso', nis: '2024002', rataTP: 90, sas: 92 },
  { id: 3, nama: 'Citra Dewi', nis: '2024003', rataTP: 76, sas: 78 },
  { id: 4, nama: 'Dian Pratama', nis: '2024004', rataTP: 68, sas: 70 },
  { id: 5, nama: 'Eka Rahayu', nis: '2024005', rataTP: 88, sas: null },
]);

const kalkulasiRapor = (siswa) => {
  if (siswa.sas === null || siswa.sas === '') return '-';
  const final = (siswa.rataTP * bobotTP) + (siswa.sas * bobotSAS);
  return Math.round(final);
};
</script>

<style scoped>
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>
