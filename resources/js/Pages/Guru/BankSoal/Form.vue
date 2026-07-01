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

      <!-- Box Impor Massal -->
      <div class="rounded-2xl border border-dashed border-indigo-500/30 p-5 bg-indigo-500/5 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
          <h3 class="text-sm font-bold text-indigo-400">Punya File Soal Word?</h3>
          <p class="text-xs text-slate-400">Salin (Copy) semua teks soal dari Microsoft Word dan tempel di sini untuk membuat kuis secara instan.</p>
        </div>
        <button 
          type="button" 
          @click="showImportModal = true"
          class="flex-shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors shadow-lg cursor-pointer"
        >
          <span>📋</span> Impor Massal dari Word
        </button>
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

  <!-- Modal Impor Soal Massal -->
  <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/85 backdrop-blur-md" @click="showImportModal = false"></div>

    <!-- Modal Content -->
    <div class="relative w-full max-w-4xl rounded-2xl border border-white/12 shadow-2xl flex flex-col md:flex-row overflow-hidden z-10" style="background: #111827">
      
      <!-- KIRI: Aturan & Panduan Ketat -->
      <div class="md:w-5/12 bg-black/30 p-6 border-b md:border-b-0 md:border-r border-white/8 space-y-4 max-h-[90vh] overflow-y-auto">
        <h3 class="text-sm font-black text-white flex items-center gap-1.5 border-b border-white/8 pb-2">
          <span>⚠️</span> ATURAN KETAT PENULISAN SOAL
        </h3>
        
        <div class="space-y-3.5 text-xs">
          <p class="text-slate-400 leading-relaxed">
            Format teks yang ditempelkan harus mengikuti struktur berikut agar terbaca sempurna oleh sistem:
          </p>

          <div class="space-y-3">
            <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-xl p-3 space-y-1">
              <div class="font-bold text-indigo-300">1. Awal Pertanyaan Soal:</div>
              <p class="text-slate-400 leading-relaxed">Diawali nomor soal diikuti titik dan spasi.</p>
              <div class="font-mono text-[10px] text-slate-500">Cth: "1. Perangkat yang..."</div>
            </div>

            <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-3 space-y-1">
              <div class="font-bold text-emerald-300">2. Pilihan Ganda (A - E):</div>
              <p class="text-slate-400 leading-relaxed">Wajib berurutan diawali huruf kapital, titik, dan spasi. Taruh baris per baris di bawah soal.</p>
              <div class="font-mono text-[10px] text-slate-500">Cth: "A. Modem"</div>
            </div>

            <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-3 space-y-1">
              <div class="font-bold text-amber-300">3. Kunci Jawaban:</div>
              <p class="text-slate-400 leading-relaxed">Ditulis menggunakan kata kunci "Jawaban:" di bawah pilihan E.</p>
              <div class="font-mono text-[10px] text-slate-500">Cth: "Jawaban: B. Local Area Network"</div>
            </div>

            <div class="bg-purple-500/10 border border-purple-500/20 rounded-xl p-3 space-y-1">
              <div class="font-bold text-purple-300">4. Pembatas Antar Soal:</div>
              <p class="text-slate-400 leading-relaxed">Pisahkan setiap soal baru dengan **satu baris kosong (Enter 2x)**.</p>
            </div>
          </div>

          <!-- Contoh Template Visual -->
          <div class="bg-white/5 rounded-xl p-3 border border-white/5 space-y-1.5 font-mono text-[10px] text-slate-400">
            <div class="text-[9px] font-bold text-slate-500 uppercase">Contoh Soal Word / Teks:</div>
            <div>1. Kepanjangan dari LAN adalah ....</div>
            <div>A. Local Access Network</div>
            <div>B. Local Area Network</div>
            <div>C. Large Area Network</div>
            <div>Jawaban: B. Local Area Network</div>
          </div>
        </div>
      </div>

      <!-- KANAN: Textarea & Aksi -->
      <div class="md:w-7/12 p-6 flex flex-col justify-between space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-white/8 pb-2">
          <h3 class="text-sm font-bold text-white flex items-center gap-1.5">
            <span>📋</span> Tempel Teks Soal Di Sini
          </h3>
          <button @click="showImportModal = false" type="button" class="text-slate-400 hover:text-white transition-colors text-lg font-bold">×</button>
        </div>

        <div class="flex-1 flex flex-col space-y-2">
          <textarea 
            v-model="rawImportText"
            placeholder="Contoh:&#10;1. Apa kepanjangan LAN?&#10;A. Local Area Network&#10;B. Local Access Network&#10;Jawaban: A. Local Area Network"
            rows="12"
            class="w-full flex-1 bg-black/40 border border-white/10 rounded-xl p-4 text-xs font-mono text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 h-64 md:h-80"
          ></textarea>
          
          <div v-if="importError" class="text-xs text-red-400 font-bold bg-red-500/10 border border-red-500/20 p-2.5 rounded-lg">
            ⚠️ {{ importError }}
          </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
          <button 
            type="button"
            @click="parseQuestionsText"
            class="flex-1 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition-all shadow-lg cursor-pointer text-center border-0"
          >
            Proses & Impor Soal
          </button>
          <button 
            type="button"
            @click="showImportModal = false"
            class="px-5 py-2.5 bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white rounded-xl text-xs font-bold transition-colors cursor-pointer text-center border-0"
          >
            Batal
          </button>
        </div>
      </div>

    </div>
  </div>

  </AppLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
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

const showImportModal = ref(false);
const rawImportText = ref('');
const importError = ref('');

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

const sanitizeQuestion = (q) => {
  q.pertanyaan = q.pertanyaan.trim();
  q.opsi_a = q.opsi_a.trim();
  q.opsi_b = q.opsi_b.trim();
  q.opsi_c = q.opsi_c.trim();
  q.opsi_d = q.opsi_d.trim();
  q.opsi_e = q.opsi_e.trim();
  return q;
};

const parseQuestionsText = () => {
  importError.value = '';
  const text = rawImportText.value;
  if (!text.trim()) {
    importError.value = 'Silakan tempel teks soal terlebih dahulu.';
    return;
  }

  // Split into lines
  const lines = text.split(/\r?\n/);
  const parsedQuestions = [];
  let currentQuestion = null;
  
  // State: 'NONE', 'QUESTION', 'OPTIONS', 'ANSWER'
  let state = 'NONE';

  for (let i = 0; i < lines.length; i++) {
    const rawLine = lines[i];
    const line = rawLine.trim();

    // Skip empty lines unless we are collecting a multi-line question
    if (line === '') {
      if (currentQuestion && state === 'QUESTION') {
        currentQuestion.pertanyaan += '\n';
      }
      continue;
    }

    // Check for a new question starting (e.g. "1. ", "2. ")
    const newQuestionMatch = line.match(/^\s*(\d+)\.\s*(.*)/);
    if (newQuestionMatch) {
      if (currentQuestion) {
        parsedQuestions.push(sanitizeQuestion(currentQuestion));
      }
      
      currentQuestion = {
        pertanyaan: newQuestionMatch[2],
        opsi_a: '',
        opsi_b: '',
        opsi_c: '',
        opsi_d: '',
        opsi_e: '',
        jawaban_benar: 'A',
        pembahasan: ''
      };
      state = 'QUESTION';
      continue;
    }

    // Check for option line (A. , B. , C. , D. , E. )
    const optionMatch = line.match(/^\s*([A-E])\.\s*(.*)/i);
    if (optionMatch) {
      if (!currentQuestion) {
        currentQuestion = getEmptyQuestion();
      }
      const optLetter = optionMatch[1].toUpperCase();
      const optText = optionMatch[2];
      currentQuestion[`opsi_${optLetter.toLowerCase()}`] = optText;
      state = 'OPTIONS';
      continue;
    }

    // Check for correct answer line (e.g. "Jawaban: B")
    const answerMatch = line.match(/^\s*(jawaban|kunci)\s*:?\s*([A-E])(?:\s*|\.|$)/i);
    if (answerMatch) {
      if (currentQuestion) {
        currentQuestion.jawaban_benar = answerMatch[2].toUpperCase();
        state = 'ANSWER';
      }
      continue;
    }

    // Generic line text: if we are in QUESTION state, append to question
    if (currentQuestion && state === 'QUESTION') {
      currentQuestion.pertanyaan += (currentQuestion.pertanyaan ? '\n' : '') + line;
    }
  }

  // Push the final question
  if (currentQuestion) {
    parsedQuestions.push(sanitizeQuestion(currentQuestion));
  }

  if (parsedQuestions.length === 0) {
    importError.value = 'Tidak ada soal yang berhasil dibaca. Pastikan format teks Anda mengikuti petunjuk aturan.';
    return;
  }

  // Assign to form questions
  form.questions = parsedQuestions;
  showImportModal.value = false;
  rawImportText.value = '';
  
  // Show success toast
  window.dispatchEvent(new CustomEvent('toast', { 
    detail: { 
      message: `Berhasil mengimpor ${parsedQuestions.length} soal! Silakan tinjau di bawah.`, 
      type: 'success' 
    } 
  }));
};

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
