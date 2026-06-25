<template>
  <Head title="Input Nilai Sumatif" />

  <AppLayout
    title="Nilai Sumatif (Grid Assessment)"
    subtitle="Input nilai siswa berdasarkan Bab / Materi Pembelajaran yang telah dipetakan"
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
      <button class="px-4 py-2 text-xs font-semibold rounded-lg border border-white/10 text-slate-400 hover:text-white hover:bg-white/5 transition-colors">
        📥 Export / Import Excel
      </button>
      <button @click="simpanNilai" class="px-4 py-2 bg-green-600 hover:bg-green-500 rounded-lg text-sm font-semibold text-white transition-colors shadow-lg shadow-green-500/30">
        💾 Simpan Nilai
      </button>
    </template>

    <div class="space-y-6">

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

      <!-- DATA GRID -->
      <div class="rounded-2xl border border-white/8 overflow-hidden bg-[#1A2035] shadow-xl">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <!-- Header Baris 1: Kolom Info & Kolom TP (colspan) -->
              <tr class="bg-white/4 border-b border-white/8">
                <th rowspan="2" class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-r border-white/8 w-12 text-center">No</th>
                <th rowspan="2" class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-r border-white/8 w-64">Nama Siswa</th>
                <th :colspan="allTopics.length" class="px-4 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-r border-white/8 text-center bg-indigo-500/10">
                  Nilai Sumatif per Materi / Bab
                </th>
                <th rowspan="2" class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-r border-white/8 w-24 text-center">
                  Nilai Rata-rata<br/>Elemen
                </th>
                <th rowspan="2" class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider w-48">
                  Catatan Formatif<br/><span class="text-[9px] font-normal text-slate-500">(Opsional/Diferensiasi)</span>
                </th>
              </tr>
              <!-- Header Baris 2: Sub-kolom Materi/Bab -->
              <tr class="bg-white/2 border-b border-white/8">
                <th v-for="topic in allTopics" :key="topic.id_topic"
                    class="px-3 py-2 text-[10px] font-bold text-slate-300 border-r border-white/8 w-36 text-center cursor-help"
                    :title="topic.deskripsi_tp">
                  <span class="text-[9px] text-indigo-400 font-mono">[{{ topic.kode_tp }}]</span>
                  <div class="text-[10px] text-white font-semibold mt-0.5 truncate w-32 mx-auto">{{ topic.nama_topik }}</div>
                </th>
              </tr>
            </thead>
            <tbody>
              <!-- Jika Belum ada Materi -->
              <tr v-if="allTopics.length === 0">
                <td :colspan="4" class="px-4 py-8 text-center text-xs text-slate-500">
                  Belum ada Materi/Bab yang dipetakan pada menu Pemetaan Materi untuk Kelas dan Mapel ini.
                </td>
              </tr>
              
              <tr v-else v-for="(siswa, sIdx) in formNilai.students" :key="siswa.id"
                  class="border-b border-white/4 hover:bg-white/2 transition-colors">
                
                <td class="px-4 py-3 text-sm text-slate-500 text-center border-r border-white/8">{{ sIdx + 1 }}</td>
                
                <td class="px-4 py-3 border-r border-white/8">
                  <div class="text-sm font-semibold text-white">{{ siswa.nama }}</div>
                  <div class="text-[10px] text-slate-500">{{ siswa.nis }}</div>
                </td>

                <!-- Input Nilai per Materi/Topic (Bab) -->
                <td v-for="topic in allTopics" :key="topic.id_topic"
                    class="p-0 border-r border-white/8 relative group">
                  <input
                    v-model.number="siswa.nilai[topic.id_topic]"
                    type="number" min="0" max="100"
                    placeholder="-"
                    :class="[
                      'w-full h-full absolute inset-0 bg-transparent text-center text-sm font-semibold outline-none focus:bg-indigo-500/20 focus:ring-2 focus:ring-inset focus:ring-indigo-500 transition-all',
                      !siswa.nilai[topic.id_topic] ? 'text-slate-600' : 
                      siswa.nilai[topic.id_topic] < 70 ? 'text-red-400 font-bold' : 
                      siswa.nilai[topic.id_topic] >= 90 ? 'text-green-400' : 'text-white'
                    ]"
                  />
                  <!-- Spacer for absolute input -->
                  <div class="h-12"></div>
                </td>

                <!-- Rata-rata Elemen -->
                <td class="px-4 py-3 border-r border-white/8 text-center bg-white/2">
                  <div class="text-sm font-bold" :class="rataRata(siswa) < 70 ? 'text-red-400' : 'text-indigo-400'">
                    {{ rataRata(siswa) || '-' }}
                  </div>
                </td>

                <!-- Catatan -->
                <td class="p-1.5">
                  <input
                    v-model="siswa.catatan"
                    type="text"
                    placeholder="Tulis catatan..."
                    class="w-full bg-white/5 border border-transparent rounded px-2 py-1.5 text-xs text-slate-300 placeholder-slate-600 outline-none focus:border-white/20 focus:bg-white/10 transition-colors"
                  />
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
  tpList: Array,
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

// Watch filter changes to reload data
watch(() => filterForm.id_kelas, (val) => {
  if(val) router.get('/guru/nilai-sumatif', { id_kelas: val, id_mapel: filterForm.id_mapel }, { preserveState: true });
});
watch(() => filterForm.id_mapel, (val) => {
  if(val) router.get('/guru/nilai-sumatif', { id_kelas: filterForm.id_kelas, id_mapel: val }, { preserveState: true });
});

const formNilai = useForm({
  students: []
});

// Flatten TPs into dynamic sub-columns (Materi/Bab)
const allTopics = computed(() => {
  if (!props.tpList) return [];
  const list = [];
  props.tpList.forEach(tp => {
    if (tp.topics) {
      tp.topics.forEach(topic => {
        list.push({
          id_topic: topic.id_topic,
          nama_topik: topic.nama_topik,
          id_tp: tp.id_tp,
          kode_tp: tp.kode_tp,
          deskripsi_tp: tp.deskripsi_tp,
        });
      });
    }
  });
  return list;
});

onMounted(() => {
  formNilai.students = props.students || [];
});

watch(() => props.students, (newStudents) => {
  formNilai.students = newStudents || [];
});

const simpanNilai = () => {
  formNilai.post('/guru/nilai-sumatif', {
    preserveScroll: true,
    onSuccess: () => {
      alert('Semua nilai berhasil disimpan ke database!');
    }
  });
};

const rataRata = (siswa) => {
  if(!siswa || !siswa.nilai) return null;
  const scores = Object.values(siswa.nilai).filter(n => n !== null && n !== '');
  if (scores.length === 0) return null;
  const sum = scores.reduce((a, b) => a + Number(b), 0);
  return Math.round(sum / scores.length);
};
</script>

<style scoped>
/* Hilangkan spinner panah di input number */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type="number"] {
  -moz-appearance: textfield;
}
</style>
