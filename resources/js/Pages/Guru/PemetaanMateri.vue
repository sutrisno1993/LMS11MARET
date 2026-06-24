<template>
  <Head title="Pemetaan Materi — Kurikulum Merdeka" />

  <AppLayout
    title="Pemetaan Materi (CP & TP)"
    subtitle="Susun Capaian Pembelajaran dan Tujuan Pembelajaran untuk 1 Tahun Ajaran"
    :navigation="navigation"
  >
    <template #topbar-actions>
      <div class="flex items-center gap-2 mr-4">
        <label class="text-xs text-slate-500 font-semibold uppercase">Pilih Mapel:</label>
        <select v-model="selectedMapel" class="bg-white/5 border border-white/10 rounded-lg px-3 py-1.5 text-sm text-white outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
          <option v-for="mapel in mapelList" :key="mapel.id_mapel" :value="mapel.id_mapel">
            {{ mapel.nama_mapel }}
          </option>
        </select>
      </div>
      <button @click="saveAll" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-semibold text-white transition-colors shadow-lg shadow-indigo-500/30">
        💾 Simpan Pemetaan
      </button>
    </template>

    <div class="grid grid-cols-4 gap-6">

      <!-- LEFT PANEL: Penjelasan KM & Navigasi Semester -->
      <div class="col-span-1 space-y-4">
        <div class="rounded-2xl border border-indigo-500/30 bg-indigo-500/10 p-5 shadow-lg shadow-indigo-500/10">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-xl">
              💡
            </div>
            <h3 class="font-bold text-indigo-100 text-sm">Panduan K. Merdeka</h3>
          </div>
          <div class="text-xs text-indigo-200/80 space-y-2">
            <p>1. <strong>Elemen / Topik:</strong> Judul besar materi (contoh: Jaringan Komputer).</p>
            <p>2. <strong>Capaian Pembelajaran (CP):</strong> Kompetensi akhir fase yang ditetapkan pemerintah.</p>
            <p>3. <strong>Tujuan Pembelajaran (TP):</strong> Turunan dari CP yang lebih spesifik dan dapat diukur oleh guru.</p>
          </div>
        </div>

        <div class="rounded-2xl border border-white/8 p-5" style="background: var(--card)">
          <div class="text-sm font-bold mb-4">Semester Aktif</div>
          <div class="space-y-2">
            <button
              v-for="sem in [1, 2]" :key="sem"
              @click="activeSemester = sem"
              :class="[
                'w-full flex items-center justify-between p-3 rounded-xl border transition-all text-left text-sm font-semibold',
                activeSemester === sem
                  ? 'bg-indigo-500/20 border-indigo-500/50 text-indigo-300'
                  : 'bg-white/5 border-white/10 text-slate-400 hover:bg-white/10'
              ]">
              <span>Semester {{ sem }} ({{ sem === 1 ? 'Ganjil' : 'Genap' }})</span>
              <span v-if="activeSemester === sem" class="text-indigo-400">●</span>
            </button>
          </div>
        </div>
      </div>

      <!-- RIGHT PANEL: Editor Elemen & TP -->
      <div class="col-span-3 space-y-6">

        <!-- List Elemen -->
        <div v-for="(elemen, eIdx) in pemetaan" :key="elemen.id"
             class="rounded-2xl border border-white/8 overflow-hidden transition-all duration-300"
             style="background: var(--card)">
          
          <!-- Elemen Header -->
          <div class="bg-white/4 px-5 py-4 flex items-start gap-4 border-b border-white/8">
            <div class="w-8 h-8 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-sm shrink-0 mt-1">
              {{ eIdx + 1 }}
            </div>
            <div class="flex-1">
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Elemen / Topik Pembelajaran</label>
              <input v-model="elemen.nama" type="text" placeholder="Masukkan nama elemen (contoh: Topologi Jaringan)..."
                     class="w-full bg-transparent border-none p-0 text-lg font-bold text-white placeholder-slate-600 focus:ring-0 outline-none" />
              
              <div class="mt-4">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Deskripsi Capaian Pembelajaran (CP)</label>
                <textarea v-model="elemen.cp" rows="2" placeholder="Pada akhir fase F, peserta didik mampu..."
                          class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-slate-300 placeholder-slate-600 outline-none focus:border-indigo-500/50 transition-colors resize-y"></textarea>
              </div>
            </div>
            <button @click="hapusElemen(eIdx)" class="text-slate-500 hover:text-red-400 p-2 transition-colors">🗑️</button>
          </div>

          <!-- Tujuan Pembelajaran (TP) List -->
          <div class="p-5">
            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3 flex justify-between items-center">
              <span>Tujuan Pembelajaran (TP)</span>
              <span class="text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded">{{ elemen.tpList.length }} TP</span>
            </div>

            <div class="space-y-3">
              <div v-for="(tp, tIdx) in elemen.tpList" :key="tp.id"
                   class="flex items-start gap-3 group relative">
                <div class="w-6 h-6 rounded bg-white/10 text-slate-400 flex items-center justify-center text-xs font-mono shrink-0 mt-1">
                  {{ eIdx + 1 }}.{{ tIdx + 1 }}
                </div>
                <div class="flex-1">
                  <textarea v-model="tp.deskripsi" rows="1" placeholder="Peserta didik dapat memahami konsep dasar..."
                            class="w-full bg-white/2 border border-white/8 rounded-lg px-3 py-2 text-sm text-slate-300 placeholder-slate-600 outline-none focus:border-green-500/50 transition-colors resize-none overflow-hidden"
                            @input="autoResize($event.target)"></textarea>
                </div>
                <!-- Pengaturan Bobot Formatif/Sumatif -->
                <div class="flex items-center gap-2 shrink-0 mt-1 opacity-60 group-hover:opacity-100 transition-opacity">
                  <div class="text-xs bg-slate-800 border border-white/10 rounded px-2 py-1.5 flex items-center gap-1" title="Jam Pelajaran Dialokasikan">
                    ⏱ <input v-model="tp.alokasiJP" type="number" class="w-8 bg-transparent text-center outline-none border-b border-white/20 focus:border-white text-white p-0" /> JP
                  </div>
                  <button @click="hapusTP(eIdx, tIdx)" class="w-7 h-7 rounded hover:bg-red-500/20 text-slate-500 hover:text-red-400 flex items-center justify-center transition-colors">✕</button>
                </div>
              </div>
            </div>

            <button @click="tambahTP(eIdx)" class="mt-4 flex items-center gap-2 text-xs font-bold text-indigo-400 hover:text-indigo-300 transition-colors">
              <span class="w-5 h-5 rounded-full bg-indigo-500/20 flex items-center justify-center">+</span> Tambah Tujuan Pembelajaran (TP)
            </button>
          </div>
        </div>

        <!-- Tambah Elemen Baru -->
        <button @click="tambahElemen" class="w-full py-4 rounded-2xl border-2 border-dashed border-white/10 hover:border-indigo-500/50 hover:bg-indigo-500/5 text-slate-400 hover:text-indigo-300 transition-all font-semibold text-sm flex flex-col items-center justify-center gap-2">
          <span class="text-2xl">+</span>
          <span>Tambah Elemen / Topik Baru</span>
        </button>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  mapelList: Array,
  tpList: Array,
});

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

const selectedMapel = ref(props.mapelList?.length > 0 ? props.mapelList[0].id_mapel : '');
const activeSemester = ref(1);

// Ubah data tpList menjadi format form
const form = useForm({
  id_mapel: selectedMapel.value,
  kode_tp: '',
  deskripsi_tp: '',
  semester: activeSemester.value === 1 ? 'GANJIL' : 'GENAP'
});

// Watch semester & mapel changes
watch([selectedMapel, activeSemester], () => {
  form.id_mapel = selectedMapel.value;
  form.semester = activeSemester.value === 1 ? 'GANJIL' : 'GENAP';
});

// Mapping existing TPs per mapel (Display Only for now)
const existingTps = computed(() => {
  if (!props.tpList) return [];
  return props.tpList.filter(tp => 
    tp.id_mapel === selectedMapel.value && 
    tp.semester === (activeSemester.value === 1 ? 'GANJIL' : 'GENAP')
  );
});

// Data draf UI (Kita simpan secara manual sementara)
const pemetaan = ref([
  {
    id: Date.now(),
    nama: 'Topik Baru (Draft)',
    cp: '',
    tpList: [{ id: Date.now() + 1, deskripsi: '', alokasiJP: 2 }]
  }
]);

const tambahElemen = () => {
  pemetaan.value.push({
    id: Date.now(),
    nama: '',
    cp: '',
    tpList: [{ id: Date.now() + 1, deskripsi: '', alokasiJP: 2 }]
  });
};

const hapusElemen = (eIdx) => {
  if (confirm('Hapus elemen materi ini beserta semua TP-nya?')) {
    pemetaan.value.splice(eIdx, 1);
  }
};

const tambahTP = (eIdx) => {
  pemetaan.value[eIdx].tpList.push({ id: Date.now(), deskripsi: '', alokasiJP: 2 });
};

const hapusTP = (eIdx, tIdx) => {
  pemetaan.value[eIdx].tpList.splice(tIdx, 1);
};

const saveAll = () => {
  // Ambil data pertama di form draf untuk disimpan
  const firstTp = pemetaan.value[0]?.tpList[0];
  if (!firstTp || !firstTp.deskripsi) {
    alert('Deskripsi TP tidak boleh kosong!');
    return;
  }
  
  form.kode_tp = 'TP-' + Math.floor(Math.random() * 100); // Auto generate kode untuk contoh
  form.deskripsi_tp = firstTp.deskripsi;
  
  form.post('/guru/pemetaan-materi', {
    preserveScroll: true,
    onSuccess: () => {
      alert('Tujuan Pembelajaran berhasil disimpan ke database!');
      pemetaan.value[0].tpList[0].deskripsi = ''; // Reset draf
    }
  });
};

const autoResize = (target) => {
  target.style.height = 'auto';
  target.style.height = target.scrollHeight + 'px';
};
</script>
