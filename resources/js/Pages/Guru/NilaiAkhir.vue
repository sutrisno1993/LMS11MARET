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
        <select v-model="filterForm.id_kelas" class="bg-[#111827] border border-white/10 rounded-lg px-3 py-1.5 text-sm text-white outline-none focus:border-indigo-500">
          <option v-for="kelas in kelasList" :key="kelas.id_kelas" :value="kelas.id_kelas">
            {{ kelas.nama_kelas }}
          </option>
        </select>
      </div>
      <button @click="simpanNilaiRapor" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-semibold text-white transition-colors shadow-lg shadow-indigo-500/30 flex items-center gap-2">
        <span>⚙️</span> Sinkronisasi ke Rapor
      </button>
    </template>

    <div class="space-y-6">
      
      <!-- Top Stats -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
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
          <div class="mt-2 text-2xl font-black text-white">{{ formNilai.students.length }} <span class="text-sm font-normal text-slate-400">Siswa</span></div>
          <div class="text-xs text-green-400 mt-1">✓ Kelas aktif terpilih</div>
        </div>

        <div class="rounded-2xl border border-white/8 p-4 bg-white/2">
          <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Rata-rata Kelas (Rapor)</div>
          <div class="mt-2 text-2xl font-black text-white">{{ rataRataKelas || '-' }}</div>
          <div class="text-xs text-slate-400 mt-1">Berdasarkan siswa dengan nilai rapor aktif</div>
        </div>
      </div>

      <!-- FILTER & INFO BAR -->
      <div class="flex items-end justify-between">
        <div class="flex gap-4">
          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Mata Pelajaran</label>
            <select v-model="filterForm.id_mapel" class="bg-[#111827] border border-white/10 rounded-lg px-4 py-2 text-sm text-white outline-none focus:border-indigo-500">
              <option v-for="mapel in mapelList" :key="mapel.id_mapel" :value="mapel.id_mapel">
                {{ mapel.nama_mapel }}
              </option>
            </select>
          </div>
        </div>

        <div class="text-right">
          <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Status Pengisian</div>
          <div class="text-sm font-semibold text-yellow-400">Belum Lengkap (Draf)</div>
        </div>
      </div>

      <!-- Main Table -->
      <div class="rounded-2xl border border-white/8 overflow-hidden bg-[#1A2035] shadow-xl">
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
              <tr v-if="formNilai.students.length === 0">
                <td colspan="6" class="px-4 py-8 text-center text-xs text-slate-500">
                  Tidak ada data siswa untuk filter terpilih.
                </td>
              </tr>
              <tr v-else v-for="(siswa, idx) in formNilai.students" :key="siswa.id" class="border-b border-white/4 hover:bg-white/2 transition-colors">
                <td class="px-4 py-3 text-sm text-slate-500 text-center border-r border-white/8">{{ idx + 1 }}</td>
                <td class="px-4 py-3 border-r border-white/8">
                  <div class="text-sm font-semibold text-white">{{ siswa.nama }}</div>
                  <div class="text-[10px] text-slate-500">{{ siswa.nis }}</div>
                </td>
                
                <!-- Rata-rata TP (Readonly, calculated from database) -->
                <td class="px-4 py-3 text-center border-r border-white/8 font-mono text-sm" :class="siswa.rataTP < 70 ? 'text-red-400' : 'text-slate-300'">
                  {{ siswa.rataTP || '-' }}
                </td>

                <!-- Nilai SAS (Inputable) -->
                <td class="p-0 border-r border-white/8 bg-green-500/5 relative">
                  <input
                    v-model.number="siswa.sas"
                    type="number" min="0" max="100"
                    placeholder="-"
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
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch, onMounted, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  kelasList: Array,
  mapelList: Array,
  selectedKelas: [String, Number],
  selectedMapel: [String, Number],
  students: Array,
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

// Form filter
const filterForm = useForm({
  id_kelas: props.selectedKelas,
  id_mapel: props.selectedMapel,
});

// Watch filters
watch(() => filterForm.id_kelas, (val) => {
  if(val) router.get('/guru/nilai-akhir', { id_kelas: val, id_mapel: filterForm.id_mapel }, { preserveState: true });
});
watch(() => filterForm.id_mapel, (val) => {
  if(val) router.get('/guru/nilai-akhir', { id_kelas: filterForm.id_kelas, id_mapel: val }, { preserveState: true });
});

const formNilai = useForm({
  id_kelas: props.selectedKelas,
  id_mapel: props.selectedMapel,
  students: []
});

onMounted(() => {
  formNilai.students = props.students || [];
});

watch(() => props.students, (newStudents) => {
  formNilai.students = newStudents || [];
});

// Update form filters on prop updates
watch(() => props.selectedKelas, (val) => {
  filterForm.id_kelas = val;
  formNilai.id_kelas = val;
});
watch(() => props.selectedMapel, (val) => {
  filterForm.id_mapel = val;
  formNilai.id_mapel = val;
});

const bobotTP = 0.7;
const bobotSAS = 0.3;

const kalkulasiRapor = (siswa) => {
  if (siswa.sas === null || siswa.sas === '') return '-';
  if (siswa.rataTP === null || siswa.rataTP === '') return Math.round(siswa.sas * bobotSAS);
  const final = (siswa.rataTP * bobotTP) + (siswa.sas * bobotSAS);
  return Math.round(final);
};

const rataRataKelas = computed(() => {
  const activeScores = formNilai.students
    .map(s => kalkulasiRapor(s))
    .filter(score => score !== '-');
  if (activeScores.length === 0) return null;
  const sum = activeScores.reduce((a, b) => a + Number(b), 0);
  return (sum / activeScores.length).toFixed(1);
});

const simpanNilaiRapor = () => {
  formNilai.post('/guru/nilai-akhir', {
    preserveScroll: true,
    onSuccess: () => {
      alert('Nilai Rapor berhasil disinkronisasi ke database!');
    }
  });
};
</script>

<style scoped>
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type="number"] {
  -moz-appearance: textfield;
}
</style>
