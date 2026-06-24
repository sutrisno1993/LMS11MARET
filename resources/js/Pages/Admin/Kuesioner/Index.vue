<template>
  <Head title="Manajemen Kuesioner" />

  <AppLayout
    title="Evaluasi Kinerja"
    subtitle="Kelola kuesioner siswa untuk Guru Mapel dan Wali Kelas"
    :navigation="navigation"
  >
    <div class="space-y-6">
      
      <!-- Header Actions -->
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-white">Daftar Kuesioner</h2>
        <button @click="showModal = true" class="px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors shadow-lg shadow-indigo-500/20 flex items-center gap-2">
          <span>➕</span> Buat Kuesioner Baru
        </button>
      </div>

      <!-- Kuesioner List -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="q in questionnaires" :key="q.id_questionnaire" 
             class="rounded-2xl border p-5 transition-all relative overflow-hidden group" 
             :class="q.is_active ? 'border-green-500/50 bg-green-500/5' : 'border-white/10'" 
             style="background: var(--card)">
          
          <div v-if="q.is_active" class="absolute top-0 right-0 w-16 h-16">
            <div class="absolute transform rotate-45 bg-green-500 text-white text-[10px] font-bold py-1 right-[-35px] top-[32px] w-[170px] text-center shadow-lg">
              AKTIF
            </div>
          </div>

          <div class="flex items-start justify-between mb-4">
            <div class="px-3 py-1 rounded-lg text-xs font-semibold" 
                 :class="q.tipe === 'GURU_MAPEL' ? 'bg-blue-500/20 text-blue-400' : 'bg-purple-500/20 text-purple-400'">
              {{ q.tipe === 'GURU_MAPEL' ? 'Guru Mapel' : 'Wali Kelas' }}
            </div>
            
            <button @click="deleteQ(q.id_questionnaire)" class="text-slate-500 hover:text-red-400 transition-colors opacity-0 group-hover:opacity-100">
              🗑️
            </button>
          </div>

          <h3 class="text-base font-bold text-white mb-2">{{ q.judul }}</h3>
          <p class="text-xs text-slate-400 mb-4 line-clamp-2">{{ q.deskripsi || 'Tanpa deskripsi' }}</p>
          
          <div class="flex items-center justify-between mt-auto pt-4 border-t border-white/5">
            <span class="text-xs font-bold text-indigo-400">{{ q.questions_count }} Pertanyaan</span>
            
            <div class="flex items-center gap-2">
              <a :href="`/admin/kuesioner/${q.id_questionnaire}/report`" class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-xs font-semibold text-white transition-colors">
                Laporan
              </a>
              <button @click="toggleStatus(q)" 
                      class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors"
                      :class="q.is_active ? 'bg-red-500/20 text-red-400 hover:bg-red-500/30' : 'bg-green-500/20 text-green-400 hover:bg-green-500/30'">
                {{ q.is_active ? 'Tutup' : 'Buka' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Create -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div class="w-full max-w-2xl bg-[#0B0F19] border border-white/10 rounded-2xl p-6 shadow-2xl overflow-y-auto max-h-[90vh]">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-white">Buat Kuesioner Baru</h3>
            <button @click="showModal = false" class="text-slate-400 hover:text-white">✕</button>
          </div>

          <form @submit.prevent="submit" class="space-y-5">
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-xs font-semibold text-slate-400">Judul Kuesioner</label>
                <input v-model="form.judul" required type="text" placeholder="Contoh: Evaluasi Guru Ganjil 2026" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500">
              </div>
              <div class="space-y-2">
                <label class="text-xs font-semibold text-slate-400">Target Evaluasi</label>
                <select v-model="form.tipe" required class="w-full bg-[#0B0F19] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500">
                  <option value="GURU_MAPEL">Guru Mata Pelajaran (Semua Guru)</option>
                  <option value="WALI_KELAS">Wali Kelas Saja</option>
                </select>
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-xs font-semibold text-slate-400">Deskripsi/Instruksi untuk Siswa</label>
              <textarea v-model="form.deskripsi" rows="2" placeholder="Silakan nilai kinerja guru dengan jujur..." class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500"></textarea>
            </div>

            <div class="space-y-3 pt-4 border-t border-white/10">
              <div class="flex items-center justify-between">
                <label class="text-xs font-bold text-white">Daftar Pertanyaan (Rating 1-5 Bintang)</label>
                <button type="button" @click="addQ" class="text-xs text-indigo-400 hover:text-indigo-300 font-bold">+ Tambah</button>
              </div>
              
              <div v-for="(q, index) in form.questions" :key="index" class="flex gap-2">
                <div class="w-8 h-10 rounded-lg bg-white/5 flex items-center justify-center text-xs font-bold text-slate-500 flex-shrink-0">{{ index + 1 }}</div>
                <input v-model="form.questions[index]" required type="text" placeholder="Cth: Guru mengajar materi dengan sangat jelas" class="flex-1 bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-indigo-500">
                <button type="button" v-if="form.questions.length > 1" @click="removeQ(index)" class="w-10 h-10 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 flex items-center justify-center flex-shrink-0">✕</button>
              </div>
            </div>

            <div class="pt-4 flex justify-end gap-3">
              <button type="button" @click="showModal = false" class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-300 hover:bg-white/5 transition-colors">Batal</button>
              <button type="submit" :disabled="form.processing" class="px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors disabled:opacity-50">
                Simpan Kuesioner
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  questionnaires: Array
});

const showModal = ref(false);

const form = useForm({
  judul: '',
  deskripsi: '',
  tipe: 'GURU_MAPEL',
  questions: ['']
});

const addQ = () => form.questions.push('');
const removeQ = (i) => form.questions.splice(i, 1);

const submit = () => {
  form.post('/admin/kuesioner', {
    onSuccess: () => {
      showModal.value = false;
      form.reset();
    }
  });
};

const toggleStatus = (q) => {
  if (confirm(`Yakin ingin ${q.is_active ? 'menutup' : 'membuka'} kuesioner ini?`)) {
    router.post(`/admin/kuesioner/${q.id_questionnaire}/toggle`);
  }
};

const deleteQ = (id) => {
  if (confirm('Yakin ingin menghapus kuesioner secara permanen beserta semua responnya?')) {
    router.delete(`/admin/kuesioner/${id}`);
  }
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
