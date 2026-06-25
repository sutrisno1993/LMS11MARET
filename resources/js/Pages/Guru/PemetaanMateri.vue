<template>
  <Head title="Pemetaan Materi — Kurikulum Merdeka" />

  <AppLayout
    title="Pemetaan Materi (CP & TP)"
    subtitle="Susun Capaian Pembelajaran, Tujuan Pembelajaran, dan Sub-materi"
    :navigation="navigation"
  >
    <template #topbar-actions>
      <!-- Dropdown Pilih Kelas -->
      <div class="flex items-center gap-1.5 sm:gap-2 mr-2 sm:mr-4">
        <label class="text-xs text-slate-500 font-semibold uppercase hidden sm:inline">Kelas:</label>
        <select v-model="selectedKelas" class="bg-[#111827] sm:bg-white/5 border border-white/10 rounded-lg px-2 py-1.5 sm:px-3 text-xs sm:text-sm text-white outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 max-w-[120px] sm:max-w-[200px] md:max-w-none truncate">
          <option v-for="cls in uniqueClasses" :key="cls.id_kelas" :value="cls.id_kelas">
            {{ cls.nama_kelas }}
          </option>
        </select>
      </div>

      <!-- Dropdown Pilih Mapel -->
      <div class="flex items-center gap-1.5 sm:gap-2 mr-2 sm:mr-4">
        <label class="text-xs text-slate-500 font-semibold uppercase hidden sm:inline">Mapel:</label>
        <select v-model="selectedMapel" class="bg-[#111827] sm:bg-white/5 border border-white/10 rounded-lg px-2 py-1.5 sm:px-3 text-xs sm:text-sm text-white outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 max-w-[120px] sm:max-w-[200px] md:max-w-none truncate">
          <option v-for="mapel in availableMapels" :key="mapel.id_mapel" :value="mapel.id_mapel">
            {{ mapel.nama_mapel }}
          </option>
        </select>
      </div>
    </template>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

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
            <p>1. <strong>Elemen / Topik:</strong> Kategori materi besar (contoh: Jaringan Komputer).</p>
            <p>2. <strong>Capaian Pembelajaran (CP):</strong> Kompetensi utama yang wajib dicapai oleh siswa.</p>
            <p>3. <strong>Tujuan Pembelajaran (TP):</strong> Kompetensi terukur turunan dari CP.</p>
            <p>4. <strong>Materi (Sub-topik):</strong> Judul bab pembelajaran riil untuk mencapai kompetensi.</p>
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
      <div class="col-span-1 lg:col-span-3 space-y-6">

        <!-- KONDISI SEDANG EDIT / TAMBAH ELEMEN BARU -->
        <div v-if="isEditing" class="rounded-2xl border border-indigo-500/30 p-5 space-y-4 shadow-lg shadow-indigo-500/10" style="background: var(--card)">
          <div class="flex items-center justify-between border-b border-white/8 pb-3">
            <h3 class="text-sm font-bold text-white">
              {{ editorForm.id_element ? '✏️ Edit Elemen & CP' : '➕ Tambah Elemen & CP Baru' }}
            </h3>
            <button @click="cancelEdit" class="text-xs text-slate-400 hover:text-white transition-colors">Batal</button>
          </div>

          <div class="space-y-3">
            <div>
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Nama Elemen / Topik</label>
              <input v-model="editorForm.nama_elemen" type="text" placeholder="Masukkan nama elemen (contoh: Jaringan Komputer)..."
                     class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 outline-none focus:border-indigo-500/50 transition-colors" />
            </div>

            <div>
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Deskripsi Capaian Pembelajaran (CP)</label>
              <textarea v-model="editorForm.deskripsi_cp" rows="3" placeholder="Pada akhir fase ini, peserta didik mampu..."
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-slate-200 placeholder-slate-600 outline-none focus:border-indigo-500/50 transition-colors resize-none"></textarea>
            </div>

            <!-- List TPs under this Element -->
            <div class="border-t border-white/5 pt-4 space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-indigo-400 uppercase tracking-widest">Tujuan Pembelajaran (TP) & Materi</span>
                <button @click="addNewTpInEditor" class="px-3 py-1 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-400 text-[10px] font-bold rounded-lg border border-indigo-500/20 transition-colors">
                  + Tambah TP
                </button>
              </div>

              <div v-for="(tp, tpIdx) in editorForm.tps" :key="tpIdx" class="p-4 rounded-xl bg-white/3 border border-white/5 space-y-3 relative group">
                <button @click="removeTpInEditor(tpIdx)" class="absolute top-4 right-4 text-slate-500 hover:text-red-400 text-xs transition-colors" title="Hapus TP">✕</button>
                
                <div class="grid grid-cols-6 gap-3">
                  <div class="col-span-1">
                    <label class="block text-[8px] font-bold text-slate-500 uppercase mb-1">Kode</label>
                    <input v-model="tp.kode_tp" type="text" class="w-full bg-white/5 border border-white/10 rounded-lg px-2 py-1.5 text-xs text-white text-center font-mono font-bold" />
                  </div>
                  <div class="col-span-5 pr-6">
                    <label class="block text-[8px] font-bold text-slate-500 uppercase mb-1">Deskripsi Tujuan Pembelajaran (TP)</label>
                    <textarea v-model="tp.deskripsi_tp" rows="1" placeholder="Peserta didik dapat memahami..." class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-slate-200 outline-none resize-none"></textarea>
                  </div>
                </div>

                <!-- Sub-topics / Materi List -->
                <div class="pl-4 border-l-2 border-indigo-500/30 space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Materi / Sub-topik Pembahasan</span>
                    <button @click="addNewTopicInEditor(tpIdx)" class="text-[9px] text-green-400 font-bold hover:text-green-300 transition-colors">
                      + Tambah Materi
                    </button>
                  </div>

                  <div v-for="(topic, tpcIdx) in tp.topics" :key="tpcIdx" class="flex items-center gap-2">
                    <input v-model="tp.topics[tpcIdx]" type="text" placeholder="Masukkan materi (contoh: Jaringan 5G)..."
                           class="flex-1 bg-white/2 border border-white/5 rounded-lg px-2 py-1 text-[11px] text-slate-300 outline-none focus:border-green-500/30" />
                    <button @click="removeTopicInEditor(tpIdx, tpcIdx)" class="text-[10px] text-slate-600 hover:text-red-400 px-1">✕</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-white/8">
              <button @click="cancelEdit" class="px-4 py-2 border border-white/10 rounded-xl text-xs font-semibold text-slate-300 hover:bg-white/5 transition-colors">Batal</button>
              <button @click="confirmSaveElement" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-xl text-xs font-semibold text-white transition-colors shadow-lg shadow-indigo-500/20">
                Simpan Pemetaan
              </button>
            </div>
          </div>
        </div>

        <!-- LIST ELEMEN DAN TP AKTIF -->
        <div v-if="!isEditing" class="space-y-6">
          
          <!-- Tombol Tambah Elemen Baru -->
          <div class="flex justify-end">
            <button @click="startCreateNew" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 rounded-xl text-xs font-bold text-white transition-colors shadow-lg shadow-indigo-500/30">
              ➕ Tambah Elemen & CP Baru
            </button>
          </div>

          <!-- Legacy TPs Fallback (jika ada data TP lama tanpa elemen) -->
          <div v-if="displayedLegacyTps.length > 0" class="rounded-2xl border border-yellow-500/30 bg-yellow-500/5 p-5 space-y-4">
            <div class="flex items-center gap-3">
              <span class="text-xl">⚠️</span>
              <div>
                <h4 class="font-bold text-sm text-yellow-300">Tujuan Pembelajaran Terpisah (Legacy)</h4>
                <p class="text-[10px] text-yellow-200/70">TP berikut terdaftar di database tetapi belum dikelompokkan ke dalam Elemen CP manapun.</p>
              </div>
            </div>
            <div class="space-y-2">
              <div v-for="tp in displayedLegacyTps" :key="tp.id_tp" class="p-3 rounded-xl bg-white/3 border border-white/5 flex items-start justify-between gap-3 text-xs">
                <div>
                  <span class="font-mono font-bold text-yellow-400 bg-yellow-500/10 px-1.5 py-0.5 rounded mr-2">{{ tp.kode_tp }}</span>
                  <span class="text-slate-300">{{ tp.deskripsi_tp }}</span>
                </div>
                <button @click="deleteTpLegacy(tp.id_tp)" class="text-red-400 hover:text-red-300 font-bold">Hapus</button>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="displayedElements.length === 0 && displayedLegacyTps.length === 0" class="rounded-2xl border border-white/8 p-12 text-center text-slate-500" style="background: var(--card)">
            <span class="text-3xl">🗺️</span>
            <h4 class="font-bold text-slate-300 mt-3 mb-1">Belum ada Pemetaan Materi</h4>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">Silakan susun Capaian Pembelajaran, Tujuan Pembelajaran, beserta sub-materi di kelas ini.</p>
          </div>

          <!-- Looping Elemen -->
          <div v-for="(element, elIdx) in displayedElements" :key="element.id_element" class="rounded-2xl border border-white/8 overflow-hidden transition-all duration-300" style="background: var(--card)">
            <!-- Header Elemen -->
            <div class="bg-white/4 px-5 py-4 border-b border-white/8 flex items-start justify-between gap-4">
              <div class="flex-1 min-w-0">
                <div class="text-[9px] font-bold text-indigo-400 uppercase tracking-widest mb-1">Elemen Pembelajaran</div>
                <h4 class="text-base font-bold text-white leading-snug">{{ element.nama_elemen }}</h4>
                <div class="mt-3">
                  <div class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Capaian Pembelajaran (CP)</div>
                  <p class="text-xs text-slate-300 leading-relaxed bg-black/10 p-3 rounded-xl border border-white/5">{{ element.deskripsi_cp }}</p>
                </div>
              </div>

              <div class="flex items-center gap-1 shrink-0">
                <button @click="startEdit(element)" class="p-2 hover:bg-white/5 text-slate-400 hover:text-white rounded-xl transition-all" title="Edit Elemen & CP">
                  ✏️ Edit
                </button>
                <button @click="deleteElement(element.id_element)" class="p-2 hover:bg-red-500/10 text-slate-400 hover:text-red-400 rounded-xl transition-all" title="Hapus Elemen">
                  🗑️ Hapus
                </button>
              </div>
            </div>

            <!-- List TPs -->
            <div class="p-5 space-y-4">
              <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Tujuan Pembelajaran (TP) & Sub-materi</div>

              <div v-for="tp in element.tps" :key="tp.id_tp" class="p-4 rounded-xl bg-white/3 border border-white/5 flex items-start gap-4">
                <div class="w-8 h-8 rounded-lg bg-indigo-500/15 text-indigo-400 flex items-center justify-center font-mono text-xs font-bold shrink-0 mt-0.5 border border-indigo-500/20">
                  {{ tp.kode_tp }}
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm text-slate-200 leading-relaxed font-semibold">{{ tp.deskripsi_tp }}</p>
                  
                  <!-- List Sub-topik/Materi -->
                  <div v-if="tp.topics && tp.topics.length > 0" class="mt-3 space-y-1.5 pl-3 border-l-2 border-green-500/30">
                    <div class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Materi Pembahasan</div>
                    <div v-for="tpc in tp.topics" :key="tpc.id_topic" class="text-xs text-slate-300 flex items-center gap-2">
                      <span class="text-green-400">●</span> {{ tpc.nama_topik }}
                    </div>
                  </div>

                  <div class="flex flex-wrap gap-2 mt-3">
                    <span v-for="cls in tp.classes" :key="cls.id_kelas" class="text-[10px] bg-slate-800/80 text-slate-400 px-2 py-0.5 rounded border border-white/5 font-semibold">
                      🏫 {{ cls.nama_kelas }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>

    <!-- Modal Konfirmasi Kelas Paralel -->
    <div v-if="showParallelModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4">
      <div class="bg-[#1e293b] border border-white/10 rounded-2xl max-w-md w-full p-6 shadow-2xl animate-in fade-in zoom-in-95 duration-200">
        <h3 class="text-base font-bold text-white mb-2">Terapkan ke Kelas Lain?</h3>
        <p class="text-xs text-slate-400 mb-4">
          Anda juga mengajar mata pelajaran ini di kelas lain. Centang kelas di bawah ini jika ingin menerapkan seluruh TP di elemen ini ke kelas tersebut secara bersamaan (sinkron):
        </p>
        
        <div class="space-y-2 max-h-48 overflow-y-auto mb-6 pr-1">
          <label v-for="cls in otherParallelClasses" :key="cls.id_kelas" class="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/5 hover:bg-white/10 cursor-pointer transition-all">
            <input type="checkbox" v-model="selectedParallelClasses" :value="cls.id_kelas" class="rounded border-white/10 text-indigo-600 focus:ring-indigo-500 bg-black/20 w-4 h-4">
            <span class="text-sm font-semibold text-white">{{ cls.nama_kelas }}</span>
          </label>
        </div>

        <div class="flex gap-3">
          <button @click="closeModal" type="button" class="flex-1 px-4 py-2 border border-white/10 rounded-xl text-xs font-bold text-slate-300 hover:bg-white/5 transition-colors">
            Batal
          </button>
          <button @click="submitWithClasses" type="button" class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-xl text-xs font-bold text-white transition-colors shadow-lg shadow-indigo-500/20">
            Simpan Pemetaan
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  kelasMapelList: Array,
  elementsList: Array,
  legacyTps: Array,
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

// Dropdowns
const selectedKelas = ref(props.kelasMapelList?.length > 0 ? props.kelasMapelList[0].id_kelas : '');
const selectedMapel = ref('');
const activeSemester = ref(1);

// State
const isEditing = ref(false);
const showParallelModal = ref(false);
const selectedParallelClasses = ref([]);

// Form editor
const editorForm = ref({
  id_element: null,
  id_mapel: '',
  nama_elemen: '',
  deskripsi_cp: '',
  tps: [],
});

// Computed
const uniqueClasses = computed(() => {
  if (!props.kelasMapelList) return [];
  const seen = new Set();
  return props.kelasMapelList.filter(item => {
    if (seen.has(item.id_kelas)) return false;
    seen.add(item.id_kelas);
    return true;
  });
});

const availableMapels = computed(() => {
  if (!props.kelasMapelList) return [];
  return props.kelasMapelList.filter(item => item.id_kelas === selectedKelas.value);
});

const otherParallelClasses = computed(() => {
  if (!props.kelasMapelList || !selectedMapel.value) return [];
  return props.kelasMapelList.filter(item => 
    item.id_mapel === selectedMapel.value && 
    item.id_kelas !== selectedKelas.value
  );
});

const displayedElements = computed(() => {
  if (!props.elementsList) return [];
  
  // Filter elemen untuk mapel yang aktif
  const filtered = props.elementsList.filter(el => el.id_mapel === selectedMapel.value);
  
  // Filter TPs di dalam elemen agar sesuai semester dan kelas aktif
  return filtered.map(el => {
    const activeTps = (el.tps || []).filter(tp => 
      tp.semester === (activeSemester.value === 1 ? 'GANJIL' : 'GENAP') &&
      (tp.classes || []).some(c => c.id_kelas === selectedKelas.value)
    );
    return {
      ...el,
      tps: activeTps
    };
  }).filter(el => el.tps.length > 0 || (editorForm.value.id_element === el.id_element && isEditing.value));
});

const displayedLegacyTps = computed(() => {
  if (!props.legacyTps) return [];
  return props.legacyTps.filter(tp => 
    tp.id_mapel === selectedMapel.value && 
    tp.semester === (activeSemester.value === 1 ? 'GANJIL' : 'GENAP') &&
    (tp.classes || []).some(c => c.id_kelas === selectedKelas.value)
  );
});

// Watch selected class to automatically update subject dropdown
watch(selectedKelas, (newVal) => {
  const filtered = props.kelasMapelList.filter(item => item.id_kelas === newVal);
  if (filtered.length > 0) {
    selectedMapel.value = filtered[0].id_mapel;
  } else {
    selectedMapel.value = '';
  }
}, { immediate: true });

// Form editor handlers
const startCreateNew = () => {
  editorForm.value = {
    id_element: null,
    id_mapel: selectedMapel.value,
    nama_elemen: '',
    deskripsi_cp: '',
    tps: [
      {
        id_tp: null,
        kode_tp: 'TP-01',
        deskripsi_tp: '',
        semester: activeSemester.value === 1 ? 'GANJIL' : 'GENAP',
        topics: [''],
      }
    ],
  };
  isEditing.value = true;
};

const startEdit = (element) => {
  editorForm.value = {
    id_element: element.id_element,
    id_mapel: element.id_mapel,
    nama_elemen: element.nama_elemen,
    deskripsi_cp: element.deskripsi_cp,
    tps: element.tps.map(tp => ({
      id_tp: tp.id_tp,
      kode_tp: tp.kode_tp,
      deskripsi_tp: tp.deskripsi_tp,
      semester: tp.semester,
      // Map classes target
      target_kelas: tp.classes.map(c => c.id_kelas),
      topics: tp.topics ? tp.topics.map(t => t.nama_topik) : [''],
    })),
  };
  isEditing.value = true;
};

const cancelEdit = () => {
  isEditing.value = false;
};

const addNewTpInEditor = () => {
  const nextNum = editorForm.value.tps.length + 1;
  editorForm.value.tps.push({
    id_tp: null,
    kode_tp: 'TP-' + String(nextNum).padStart(2, '0'),
    deskripsi_tp: '',
    semester: activeSemester.value === 1 ? 'GANJIL' : 'GENAP',
    topics: [''],
  });
};

const removeTpInEditor = (idx) => {
  editorForm.value.tps.splice(idx, 1);
};

const addNewTopicInEditor = (tpIdx) => {
  editorForm.value.tps[tpIdx].topics.push('');
};

const removeTopicInEditor = (tpIdx, tpcIdx) => {
  editorForm.value.tps[tpIdx].topics.splice(tpcIdx, 1);
};

// Saving handlers
const confirmSaveElement = () => {
  if (!editorForm.value.nama_elemen.trim() || !editorForm.value.deskripsi_cp.trim()) {
    alert('Nama Elemen dan Deskripsi CP tidak boleh kosong!');
    return;
  }

  // Validasi TPs
  for (let tp of editorForm.value.tps) {
    if (!tp.deskripsi_tp.trim()) {
      alert('Deskripsi TP tidak boleh kosong!');
      return;
    }
  }

  // Tampilkan modal jika ada kelas paralel
  if (otherParallelClasses.value.length > 0) {
    selectedParallelClasses.value = [];
    showParallelModal.value = true;
  } else {
    submitElement([selectedKelas.value]);
  }
};

const closeModal = () => {
  showParallelModal.value = false;
};

const submitWithClasses = () => {
  const classesToSave = [selectedKelas.value, ...selectedParallelClasses.value];
  submitElement(classesToSave);
  closeModal();
};

const submitElement = (classesArray) => {
  // Untuk setiap TP dalam form, jika belum diset target_kelas-nya, set dengan classesArray
  editorForm.value.tps.forEach(tp => {
    // Jika edit, target_kelas bawaan dipertahankan, kecuali jika dicentang paralel
    if (!tp.id_tp) {
      tp.target_kelas = classesArray;
    } else {
      // Untuk update, sinkronkan kelas ke kelasArray
      tp.target_kelas = classesArray;
    }
  });

  router.post('/guru/pemetaan-materi', editorForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      isEditing.value = false;
      alert('Pemetaan Elemen, CP, dan TP berhasil disimpan!');
    }
  });
};

const deleteElement = (id_element) => {
  if (confirm('Apakah Anda yakin ingin menghapus Elemen beserta seluruh TP & Materi di dalamnya?')) {
    router.delete(`/guru/pemetaan-materi/element/${id_element}`, {
      preserveScroll: true,
    });
  }
};

const deleteTpLegacy = (id_tp) => {
  if (confirm('Hapus Tujuan Pembelajaran legacy ini?')) {
    router.delete(`/guru/pemetaan-materi/${id_tp}`, {
      preserveScroll: true,
    });
  }
};
</script>
