<template>
  <Head title="Buat Bank Soal" />

  <AppLayout
    title="Buat Bank Soal"
    subtitle="Ketik soal pilihan ganda dan tentukan kunci jawabannya"
    :navigation="navigation"
  >
    <form @submit.prevent="submit" class="max-w-4xl mx-auto space-y-6">
      
      <!-- Metadata Bank Soal -->
      <div class="rounded-2xl border border-white/10 p-6 space-y-5 shadow-lg" style="background: var(--card)">
        <h2 class="text-base font-bold text-white border-b border-white/10 pb-3">Informasi Paket Soal</h2>
        
        <div class="grid grid-cols-2 gap-5">
          <div class="space-y-2">
            <label class="text-xs font-semibold text-slate-400">Mata Pelajaran</label>
            <select v-model="form.id_mapel" required class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
              <option value="" disabled>Pilih Mapel...</option>
              <option v-for="m in mapelList" :key="m.id_mapel" :value="m.id_mapel">{{ m.nama_mapel }}</option>
            </select>
          </div>
          
          <div class="space-y-2">
            <label class="text-xs font-semibold text-slate-400">Judul Kuis / Asesmen</label>
            <input v-model="form.judul" type="text" required placeholder="Cth: Kuis Harian Jaringan Komputer" class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-semibold text-slate-400">Deskripsi (Opsional)</label>
          <textarea v-model="form.deskripsi" rows="2" placeholder="Catatan singkat untuk siswa..." class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
        </div>
      </div>

      <!-- Daftar Soal -->
      <div class="space-y-6">
        <div v-for="(q, index) in form.questions" :key="index" class="rounded-2xl border border-white/10 p-6 relative group" style="background: var(--card)">
          
          <div class="absolute -left-3 -top-3 w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-lg shadow-indigo-500/50">
            {{ index + 1 }}
          </div>
          
          <button v-if="form.questions.length > 1" @click.prevent="removeQuestion(index)" type="button" class="absolute top-4 right-4 text-slate-500 hover:text-red-400 transition-colors opacity-0 group-hover:opacity-100">
            🗑️ Hapus Soal
          </button>

          <div class="space-y-4">
            <div class="space-y-2">
              <label class="text-xs font-semibold text-slate-400">Pertanyaan</label>
              <textarea v-model="q.pertanyaan" required rows="3" placeholder="Ketik pertanyaan di sini..." class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-for="opt in ['A','B','C','D','E']" :key="opt" class="flex items-center gap-3">
                <input type="radio" :name="`correct_${index}`" :value="opt" v-model="q.jawaban_benar" required class="w-4 h-4 text-indigo-600 bg-black/50 border-white/20 focus:ring-indigo-500">
                <div class="flex-1 flex items-center gap-2">
                  <span class="text-xs font-bold w-4">{{ opt }}.</span>
                  <input v-model="q[`opsi_${opt.toLowerCase()}`]" type="text" required :placeholder="`Pilihan ${opt}`" class="flex-1 bg-black/50 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" :class="{'border-green-500/50 bg-green-500/5': q.jawaban_benar === opt}">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between pt-4">
        <button type="button" @click="addQuestion" class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-indigo-400 bg-indigo-500/10 hover:bg-indigo-500/20 transition-colors border border-indigo-500/20">
          <span>➕</span> Tambah Soal
        </button>

        <button type="submit" :disabled="form.processing" class="flex items-center gap-2 px-8 py-3 rounded-xl text-sm font-bold text-white transition-all hover:-translate-y-0.5 shadow-lg shadow-green-500/20 disabled:opacity-50" style="background: linear-gradient(135deg, #10B981, #059669);">
          <span>💾</span> Simpan Bank Soal
        </button>
      </div>

    </form>
  </AppLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  mapelList: Array
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

const getEmptyQuestion = () => ({
  pertanyaan: '',
  opsi_a: '', opsi_b: '', opsi_c: '', opsi_d: '', opsi_e: '',
  jawaban_benar: 'A',
  pembahasan: ''
});

const form = useForm({
  id_mapel: '',
  judul: '',
  deskripsi: '',
  questions: [getEmptyQuestion()]
});

const addQuestion = () => {
  form.questions.push(getEmptyQuestion());
};

const removeQuestion = (index) => {
  if (form.questions.length > 1) {
    form.questions.splice(index, 1);
  }
};

const submit = () => {
  form.post('/guru/bank-soal', {
    preserveScroll: true,
  });
};
</script>
