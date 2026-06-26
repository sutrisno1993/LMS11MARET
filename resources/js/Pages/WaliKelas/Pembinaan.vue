<template>
  <Head title="Pembinaan & Tindakan Disiplin Siswa" />

  <AppLayout
    title="Pembinaan & SP Siswa"
    subtitle="Pantau kehadiran kritis dan ambil tindakan pembinaan personal, pemanggilan orang tua, atau penerbitan SP"
    :navigation="navigation"
  >
    <div class="space-y-6 max-w-6xl">
      <!-- Info Header & Peringatan Kelas -->
      <div v-if="criticalStudents.length > 0" class="rounded-2xl border border-red-500/20 p-4 bg-red-500/5 flex items-start gap-4">
        <div class="p-2 bg-red-500/10 rounded-xl text-red-400 text-lg">⚠️</div>
        <div>
          <h4 class="font-bold text-red-400 text-sm">Peringatan Kehadiran Kritis Siswa</h4>
          <p class="text-xs text-slate-400 mt-1">
            Ada {{ criticalStudents.length }} siswa dengan tingkat Alpa yang tinggi. Segera berikan pembinaan atau tindakan disiplin yang sesuai.
          </p>
        </div>
      </div>

      <!-- Main Layout: Grid Kiri (Siswa & Statistik) & Kanan (Riwayat Tindakan) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Kolom Kiri: Daftar Siswa & Presensi Detail -->
        <div class="lg:col-span-7 space-y-6">
          <div class="rounded-2xl border border-white/8 overflow-hidden bg-[#1E293B]">
            <div class="px-5 py-4 border-b border-white/8 flex items-center justify-between bg-white/2">
              <h3 class="font-bold text-sm">Absensi Detail & Tindakan Siswa</h3>
              <div class="flex items-center gap-2">
                <select v-model="filterForm.semester" @change="applyFilter" class="bg-[#111827] border border-white/10 rounded-lg px-2 py-1 text-xs text-white outline-none">
                  <option value="GANJIL">Ganjil</option>
                  <option value="GENAP">Genap</option>
                </select>
              </div>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-black/20 border-b border-white/8 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                    <th class="px-4 py-3 text-center w-12">No</th>
                    <th class="px-4 py-3">Siswa</th>
                    <th class="px-3 py-3 text-center text-yellow-500">S</th>
                    <th class="px-3 py-3 text-center text-blue-400">I</th>
                    <th class="px-3 py-3 text-center text-red-400 font-bold">A</th>
                    <th class="px-4 py-3 text-center">% Hadir</th>
                    <th class="px-4 py-3 text-center w-28">Tindakan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="students.length === 0">
                    <td colspan="7" class="px-4 py-8 text-center text-xs text-slate-500">Belum ada data siswa.</td>
                  </tr>
                  <tr v-for="(siswa, idx) in students" :key="siswa.id_siswa" 
                      class="border-b border-white/4 hover:bg-white/2 transition-colors"
                      :class="{'bg-red-500/5': siswa.alpa >= 3}">
                    <td class="px-4 py-3 text-xs text-slate-500 text-center">{{ idx + 1 }}</td>
                    <td class="px-4 py-3">
                      <div class="text-sm font-semibold text-white">{{ siswa.nama }}</div>
                      <div class="text-[10px] text-slate-500 mt-0.5">{{ siswa.nis }}</div>
                    </td>
                    <td class="px-3 py-3 text-xs text-center font-bold text-yellow-500">{{ siswa.sakit }}</td>
                    <td class="px-3 py-3 text-xs text-center font-bold text-blue-400">{{ siswa.izin }}</td>
                    <td class="px-3 py-3 text-xs text-center font-black" :class="siswa.alpa >= 3 ? 'text-red-400 text-sm' : 'text-slate-400'">
                      {{ siswa.alpa }}
                    </td>
                    <td class="px-4 py-3 text-center">
                      <span class="text-xs font-bold" :class="siswa.persentase < 90 ? 'text-red-400' : 'text-green-400'">
                        {{ siswa.persentase }}%
                      </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                      <button @click="bukaModalTindakan(siswa)" class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-semibold transition-colors flex items-center gap-1 mx-auto">
                        <span>⚡</span> Tindak
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Kolom Kanan: Riwayat Tindakan Pembinaan -->
        <div class="lg:col-span-5 space-y-6">
          <div class="rounded-2xl border border-white/8 bg-[#1E293B] p-5 shadow-lg">
            <h3 class="font-bold text-sm text-white mb-4">Riwayat Tindakan Pembinaan & SP</h3>

            <div class="space-y-4 max-h-[550px] overflow-y-auto pr-1">
              <div v-if="disciplineHistory.length === 0" class="text-center py-8 text-xs text-slate-500">
                Belum ada riwayat tindakan pembinaan yang dicatat.
              </div>
              <div v-for="action in disciplineHistory" :key="action.id" 
                   class="rounded-xl border border-white/5 bg-black/20 p-3.5 relative group hover:border-white/10 transition-colors">
                <button @click="hapusTindakan(action.id)" class="absolute top-3.5 right-3.5 text-xs text-red-500 hover:text-red-400 opacity-0 group-hover:opacity-100 transition-opacity">
                  🗑️ Hapus
                </button>
                
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ action.tanggal_tindakan }}</div>
                <div class="mt-1 font-bold text-sm text-white">{{ action.student ? action.student.nama_siswa : 'Siswa Tidak Ditemukan' }}</div>
                
                <div class="mt-2.5 flex flex-wrap gap-1.5">
                  <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-slate-500/20 text-slate-400 border border-slate-500/30">
                    {{ action.kategori_kasus || 'ABSENSI' }}
                  </span>
                  <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider" :class="getBadgeClass(action.tipe_tindakan)">
                    {{ getActionLabel(action.tipe_tindakan) }}
                  </span>
                </div>

                <div v-if="action.kasus_detail" class="mt-2.5 text-xs text-slate-300">
                  <span class="text-[10px] font-bold text-slate-500 block uppercase tracking-wide">Pelanggaran/Kasus:</span>
                  <p class="mt-0.5">{{ action.kasus_detail }}</p>
                </div>

                <div v-if="action.keterangan" class="mt-2.5 text-xs text-slate-300">
                  <span class="text-[10px] font-bold text-slate-500 block uppercase tracking-wide">Catatan Pembinaan:</span>
                  <p class="mt-0.5 text-slate-400 italic">"{{ action.keterangan }}"</p>
                </div>

                <div v-if="action.tindakan_lanjut" class="mt-2.5 text-xs text-slate-300">
                  <span class="text-[10px] font-bold text-slate-500 block uppercase tracking-wide">Rencana Tindak Lanjut:</span>
                  <p class="mt-0.5 text-indigo-300">{{ action.tindakan_lanjut }}</p>
                </div>

                <div v-if="action.foto_bukti" class="mt-3 border-t border-white/5 pt-2">
                  <span class="text-[10px] font-bold text-slate-500 block mb-1">Bukti Fisik:</span>
                  <a :href="action.foto_bukti" target="_blank" class="inline-block overflow-hidden rounded-lg border border-white/10 hover:border-indigo-500/40 transition-all max-w-[120px]">
                    <img :src="action.foto_bukti" class="object-cover w-full h-16 hover:scale-105 transition-transform" />
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL TINDAKAN PEMBINAAN -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-[#1E293B] border border-white/10 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
        <div class="px-5 py-4 border-b border-white/8 bg-white/2 flex items-center justify-between">
          <h3 class="font-bold text-sm text-white">Catat Tindakan Pembinaan & SP</h3>
          <button @click="showModal = false" class="text-slate-400 hover:text-white text-lg">×</button>
        </div>

        <form @submit.prevent="submitTindakan" class="p-5 space-y-4 overflow-y-auto flex-1">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Nama Siswa</label>
              <input type="text" :value="selectedStudent.nama" readonly class="w-full bg-[#111827] border border-white/10 rounded-lg px-3 py-2 text-sm text-slate-400 outline-none" />
            </div>

            <div>
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Kategori Masalah</label>
              <select v-model="form.kategori_kasus" class="w-full bg-[#111827] border border-white/10 rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-indigo-500">
                <option value="ABSENSI">Absensi (Kehadiran)</option>
                <option value="AKADEMIK">Akademik (Tugas/Nilai)</option>
                <option value="PERILAKU">Perilaku (Kedisiplinan/Sikap)</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Detail Kasus / Pelanggaran</label>
            <textarea v-model="form.kasus_detail" rows="2" placeholder="Contoh: Merokok di kantin belakang, tidak mengumpulkan tugas SAS, dll..." class="w-full bg-[#111827] border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-600 outline-none focus:border-indigo-500"></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Jenis Tindakan / SP</label>
              <select v-model="form.tipe_tindakan" class="w-full bg-[#111827] border border-white/10 rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-indigo-500">
                <option value="PEMBINAAN_PRIBADI">Pembinaan Pribadi</option>
                <option value="PEMBINAAN_PERSONAL">Pembinaan Personal</option>
                <option value="PEMANGGILAN_ORTU">Pemanggilan Orang Tua</option>
                <option value="HOME_VISIT">Kunjungan Rumah (Home Visit)</option>
                <option value="SP_1">Surat Peringatan 1 (SP 1)</option>
                <option value="SP_2">Surat Peringatan 2 (SP 2)</option>
                <option value="SP_3">Surat Peringatan 3 (SP 3)</option>
                <option value="PERJANJIAN_TIDAK_NAIK">Perjanjian Siap Tidak Naik Kelas</option>
              </select>
            </div>

            <div>
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Tanggal Tindakan</label>
              <input type="date" v-model="form.tanggal_tindakan" class="w-full bg-[#111827] border border-white/10 rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-indigo-500" />
            </div>
          </div>

          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Catatan Pembinaan / Solusi</label>
            <textarea v-model="form.keterangan" rows="2" placeholder="Tulis rincian pembinaan atau kesepakatan di sini..." class="w-full bg-[#111827] border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-600 outline-none focus:border-indigo-500"></textarea>
          </div>

          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Rencana Tindak Lanjut</label>
            <input type="text" v-model="form.tindakan_lanjut" placeholder="Contoh: Pemantauan kehadiran 1 minggu, pengerjaan tugas susulan..." class="w-full bg-[#111827] border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-600 outline-none focus:border-indigo-500" />
          </div>

          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Foto Bukti Fisik Penanganan</label>
            <div class="mt-1 flex items-center gap-4">
              <input type="file" accept="image/*" @change="handleFileChange" class="hidden" ref="fileInput" />
              <button type="button" @click="$refs.fileInput.click()" class="px-4 py-2 border border-dashed border-white/15 hover:border-indigo-500 rounded-xl text-xs font-bold text-slate-400 hover:text-white transition-all bg-[#111827]">
                📸 Pilih / Ambil Foto
              </button>
              <span v-if="form.foto_bukti" class="text-xs text-indigo-400 truncate max-w-[200px]">
                {{ form.foto_bukti.name }}
              </span>
            </div>
            
            <!-- Preview Image -->
            <div v-if="imagePreview" class="mt-3 relative w-32 h-32 rounded-xl overflow-hidden border border-white/10">
              <img :src="imagePreview" class="w-full h-full object-cover" />
              <button type="button" @click="clearImage" class="absolute top-1.5 right-1.5 bg-black/75 hover:bg-black w-6 h-6 rounded-full flex items-center justify-center text-xs text-white">
                ✕
              </button>
            </div>
          </div>

          <div class="pt-4 border-t border-white/5 flex items-center justify-end gap-3">
            <button type="button" @click="showModal = false" class="px-4 py-2 border border-white/10 hover:bg-white/5 rounded-xl text-xs font-bold text-slate-400 hover:text-white transition-all">
              Batal
            </button>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-xl text-xs font-bold text-white transition-all shadow-lg shadow-indigo-500/20">
              Simpan Tindakan
            </button>
          </div>
        </form>
      </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  students: Array,
  kelas: Object,
  disciplineHistory: Array,
  filters: Object,
});

const navigation = [
  {
    label: 'Perwalian',
    items: [
      { href: '/walikelas/dashboard', icon: '🏠', label: 'Dashboard Kelas' },
      { href: '/walikelas/jurnal', icon: '📓', label: 'Jurnal KBM Kelas' },
      { href: '/walikelas/pembinaan', icon: '⚡', label: 'Pembinaan & SP Siswa' },
      { href: '/walikelas/p5-assessment', icon: '🌱', label: 'Asesmen P5' },
    ],
  },
];

const showModal = ref(false);
const selectedStudent = ref(null);
const fileInput = ref(null);
const imagePreview = ref(null);

const filterForm = useForm({
  semester: props.filters.semester || 'GANJIL',
  year: props.filters.year || new Date().getFullYear(),
});

const form = useForm({
  id_siswa: null,
  kategori_kasus: 'ABSENSI',
  kasus_detail: '',
  tipe_tindakan: 'PEMBINAAN_PRIBADI',
  tanggal_tindakan: new Date().toISOString().split('T')[0],
  keterangan: '',
  tindakan_lanjut: '',
  foto_bukti: null,
});

const criticalStudents = computed(() => {
  return props.students.filter(s => s.alpa >= 3);
});

const applyFilter = () => {
  router.get('/walikelas/pembinaan', {
    semester: filterForm.semester,
    year: filterForm.year,
  }, {
    preserveState: true,
  });
};

const bukaModalTindakan = (siswa) => {
  selectedStudent.value = siswa;
  form.id_siswa = siswa.id_siswa;
  form.kategori_kasus = siswa.alpa >= 3 ? 'ABSENSI' : 'PERILAKU';
  form.kasus_detail = siswa.alpa >= 3 ? `Ketidakhadiran tanpa keterangan mencapai ${siswa.alpa} kali.` : '';
  form.tipe_tindakan = siswa.alpa >= 5 ? 'SP_2' : (siswa.alpa >= 3 ? 'SP_1' : 'PEMBINAAN_PRIBADI');
  form.tanggal_tindakan = new Date().toISOString().split('T')[0];
  form.keterangan = '';
  form.tindakan_lanjut = '';
  form.foto_bukti = null;
  imagePreview.value = null;
  showModal.value = true;
};

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    form.foto_bukti = file;
    const reader = new FileReader();
    reader.onload = (event) => {
      imagePreview.value = event.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const clearImage = () => {
  form.foto_bukti = null;
  imagePreview.value = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const submitTindakan = () => {
  // Post as multipart/form-data
  form.post('/walikelas/pembinaan', {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      showModal.value = false;
    }
  });
};

const hapusTindakan = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus catatan pembinaan ini?')) {
    router.delete(`/walikelas/pembinaan/${id}`, {
      preserveScroll: true,
    });
  }
};

const getActionLabel = (type) => {
  const labels = {
    'PEMBINAAN_PRIBADI': 'Pembinaan Pribadi',
    'PEMBINAAN_PERSONAL': 'Pembinaan Personal',
    'PEMANGGILAN_ORTU': 'Pemanggilan Orang Tua',
    'HOME_VISIT': 'Home Visit (Kunjungan)',
    'SP_1': 'Surat Peringatan 1 (SP 1)',
    'SP_2': 'Surat Peringatan 2 (SP 2)',
    'SP_3': 'Surat Peringatan 3 (SP 3)',
    'PERJANJIAN_TIDAK_NAIK': 'Perjanjian Siap Tidak Naik Kelas',
  };
  return labels[type] || type;
};

const getBadgeClass = (type) => {
  if (type.startsWith('SP_') || type === 'PERJANJIAN_TIDAK_NAIK') {
    return 'bg-red-500/20 text-red-400 border border-red-500/30';
  }
  if (type === 'PEMANGGILAN_ORTU') {
    return 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30';
  }
  if (type === 'HOME_VISIT') {
    return 'bg-purple-500/20 text-purple-400 border border-purple-500/30';
  }
  return 'bg-blue-500/20 text-blue-400 border border-blue-500/30';
};
</script>

<style scoped>
</style>
