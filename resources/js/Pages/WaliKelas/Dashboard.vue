<template>
  <Head title="Dashboard Wali Kelas" />

  <AppLayout
    title="Dashboard Wali Kelas"
    subtitle="Pantau kehadiran, nilai rapor, dan performa kelas perwalian Anda"
    :navigation="navigation"
  >
    <div class="space-y-6 max-w-6xl">
      
      <!-- Ringkasan Kelas -->
      <div v-if="kelas" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-white/8 p-5 bg-gradient-to-br from-indigo-500/20 to-purple-500/10 shadow-lg">
          <div class="text-[10px] font-bold text-indigo-300 uppercase tracking-widest mb-1">Kelas Perwalian</div>
          <div class="text-3xl font-black text-white">{{ kelas.nama_kelas }}</div>
          <div class="text-xs text-indigo-200 mt-2 flex items-center gap-2 flex-wrap">
            <span>👥 {{ students.length }} Siswa</span>
            <span>•</span>
            <span>T.A {{ currentYear }} Ganjil</span>
          </div>
        </div>

        <div class="rounded-2xl border border-white/8 p-5 bg-white/2">
          <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Rata-rata Kehadiran</div>
          <div class="flex items-end gap-2">
            <div class="text-3xl font-black text-green-400">{{ rataKehadiran }}%</div>
          </div>
          <div class="text-xs text-slate-400 mt-2">Seluruh siswa bimbingan</div>
        </div>

        <div class="rounded-2xl border border-white/8 p-5 bg-white/2">
          <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Rata-rata Rapor Kelas</div>
          <div class="flex items-end gap-2">
            <div class="text-3xl font-black text-yellow-400">{{ rataRaporKelas }}</div>
          </div>
          <div class="text-xs text-slate-400 mt-2">Berdasarkan seluruh mata pelajaran</div>
        </div>

        <div class="rounded-2xl border border-white/8 p-5 bg-white/2">
          <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Poin Pelanggaran Kelas</div>
          <div class="flex items-end gap-2">
            <div class="text-3xl font-black text-white">0</div>
          </div>
          <div class="text-xs text-slate-400 mt-2">Tidak ada laporan poin aktif</div>
        </div>
      </div>

      <!-- Action & P5 Banner -->
      <div class="rounded-2xl border border-green-500/30 p-2 bg-gradient-to-r from-green-500/10 to-transparent flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="p-4 flex-1">
          <h3 class="font-bold text-white text-lg flex items-center gap-2">
            <span>🌱</span> Asesmen P5 (Profil Pelajar Pancasila)
          </h3>
          <p class="text-sm text-slate-400 mt-1">Sebagai wali kelas, Anda wajib mengisi deskripsi dan capaian P5 siswa sebelum cetak rapor akhir.</p>
        </div>
        <div class="px-4 pb-4 sm:pb-0 sm:pr-4">
          <a href="/walikelas/p5-assessment" class="w-full sm:w-auto px-5 py-3 bg-green-600 hover:bg-green-500 text-white rounded-xl text-sm font-bold transition-all shadow-lg shadow-green-500/30 flex items-center justify-center gap-2 whitespace-nowrap">
            Mulai Input P5 <span>→</span>
          </a>
        </div>
      </div>
      <!-- Daftar Siswa Perwalian -->
      <div class="rounded-2xl border border-white/8 overflow-hidden bg-[#1E293B] shadow-xl">
        <div class="px-5 py-4 border-b border-white/8 flex items-center justify-between bg-white/2">
          <h3 class="font-bold text-sm">Daftar Siswa & Progres Nilai</h3>
          <input v-model="searchQuery" type="text" placeholder="Cari nama siswa..." class="bg-[#111827] border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white outline-none focus:border-indigo-500 w-64" />
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-black/20 border-b border-white/8">
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider w-12 text-center">No</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nama Siswa / NIS</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center">Kehadiran Sem.</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center">Poin Pelanggaran</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider w-40">Kelengkapan Rapor</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center w-32">Rata-rata Rapor</th>
                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredStudents.length === 0">
                <td colspan="7" class="px-4 py-8 text-center text-xs text-slate-500">Tidak ada data siswa.</td>
              </tr>
              <tr v-else v-for="(siswa, idx) in filteredStudents" :key="siswa.id" class="border-b border-white/4 hover:bg-white/2 transition-colors" :class="{'bg-red-500/5': siswa.rataRapor !== null && siswa.rataRapor < 75}">
                <td class="px-4 py-3 text-sm text-slate-500 text-center">{{ idx + 1 }}</td>
                <td class="px-4 py-3">
                  <div class="text-sm font-semibold text-white">{{ siswa.nama }}</div>
                  <div class="text-[10px] text-slate-500 mt-0.5">{{ siswa.nis }}</div>
                </td>
                <td class="px-4 py-3 text-center">
                  <div class="text-sm font-bold" :class="siswa.kehadiran < 90 ? 'text-red-400' : 'text-green-400'">{{ siswa.kehadiran }}%</div>
                </td>
                <td class="px-4 py-3 text-center">
                  <div class="inline-block px-2 py-0.5 rounded text-xs font-bold" :class="siswa.poin > 0 ? 'bg-red-500/20 text-red-400' : 'text-slate-500'">
                    {{ siswa.poin }}
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <div class="flex-1 h-2 bg-white/10 rounded-full overflow-hidden">
                      <div class="h-full bg-indigo-500 rounded-full" :style="`width: ${siswa.raporPct}%`"></div>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400">{{ siswa.raporPct }}%</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-center">
                  <div class="text-sm font-black" :class="siswa.rataRapor < 75 ? 'text-red-400 font-black' : 'text-slate-300'">
                    {{ siswa.rataRapor || '-' }}
                  </div>
                  <div v-if="remediCount(siswa) > 0" class="inline-block text-[9px] font-bold text-red-400 bg-red-500/10 px-2 py-0.5 rounded border border-red-500/20 mt-1 uppercase tracking-wider">
                    ⚠️ {{ remediCount(siswa) }} Remedi
                  </div>
                </td>
                <td class="px-4 py-3 text-center">
                  <button @click="bukaDetailNilai(siswa)" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 transition-colors bg-indigo-500/10 px-3 py-1.5 rounded-lg border border-indigo-500/20">
                    Lihat Detail
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL DETAIL NILAI AKADEMIK SISWA -->
    <transition name="fade">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showModal = false"></div>
        
        <!-- Modal Content -->
        <div class="relative w-full max-w-2xl rounded-2xl border border-white/12 shadow-2xl p-6 overflow-hidden max-h-[85vh] flex flex-col bg-[#1E293B]">
          
          <!-- Header -->
          <div class="flex items-start justify-between border-b border-white/8 pb-4 mb-4">
            <div>
              <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>📋</span> Detail Nilai Akademik Siswa
              </h3>
              <p class="text-xs text-slate-500 mt-1">
                {{ selectedStudent.nama }} — NIS: {{ selectedStudent.nis }} (Kelas {{ kelas?.nama_kelas }})
              </p>
            </div>
            <button @click="showModal = false" class="text-slate-500 hover:text-white transition-colors text-lg font-bold">
              ✕
            </button>
          </div>

          <!-- Grade List Table -->
          <div class="overflow-y-auto flex-1 space-y-2 pr-1">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-black/20 border-b border-white/8 text-[9px] font-bold text-slate-400 uppercase tracking-wider">
                  <th class="px-4 py-2">Mata Pelajaran</th>
                  <th class="px-4 py-2 text-center w-24">Nilai SAS</th>
                  <th class="px-4 py-2 text-center w-24">Nilai Rapor</th>
                  <th class="px-4 py-2 text-center w-24">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="sub in subjects" :key="sub.id_mapel" class="border-b border-white/5 hover:bg-white/2 transition-colors">
                  <td class="px-4 py-3 text-xs font-semibold text-white">{{ sub.nama_mapel }}</td>
                  <td class="px-4 py-3 text-center text-xs font-bold text-green-400">
                    {{ selectedStudent.grades[sub.id_mapel]?.nilai_sas ?? '-' }}
                  </td>
                  <td class="px-4 py-3 text-center text-xs font-black text-indigo-400">
                    {{ selectedStudent.grades[sub.id_mapel]?.nilai_akhir ?? '-' }}
                  </td>
                  <td class="px-4 py-3 text-center">
                    <span v-if="selectedStudent.grades[sub.id_mapel]?.nilai_akhir !== null" 
                          :class="[
                            'px-2 py-0.5 rounded-lg text-[9px] font-bold uppercase tracking-wider',
                            selectedStudent.grades[sub.id_mapel].nilai_akhir >= 75 ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'
                          ]">
                      {{ selectedStudent.grades[sub.id_mapel].nilai_akhir >= 75 ? 'Lulus' : 'Remedi' }}
                    </span>
                    <span v-else class="text-[9px] text-slate-600 italic">Belum Input</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </transition>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  students: Array,
  kelas: Object,
  subjects: Array,
});

const currentYear = new Date().getFullYear();
const searchQuery = ref('');
const showModal = ref(false);
const selectedStudent = ref(null);

const remediCount = (siswa) => {
  if (!siswa || !siswa.grades) return 0;
  return Object.values(siswa.grades).filter(g => g.nilai_akhir !== null && g.nilai_akhir < 75).length;
};

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

const filteredStudents = computed(() => {
  if (!searchQuery.value) return props.students;
  return props.students.filter(s => 
    s.nama.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
    s.nis.includes(searchQuery.value)
  );
});

const rataKehadiran = computed(() => {
  if (props.students.length === 0) return 100;
  const sum = props.students.reduce((acc, s) => acc + s.kehadiran, 0);
  return Math.round(sum / props.students.length);
});

const rataRaporKelas = computed(() => {
  let totalScore = 0;
  let count = 0;

  props.students.forEach(s => {
    Object.values(s.grades).forEach(g => {
      if (g.nilai_akhir !== null) {
        totalScore += g.nilai_akhir;
        count++;
      }
    });
  });

  return count > 0 ? (totalScore / count).toFixed(1) : '-';
});

const bukaDetailNilai = (siswa) => {
  selectedStudent.value = siswa;
  showModal.value = true;
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
