<template>
  <Head title="Ujian Live" />

  <AppLayout
    title="Lembar Ujian"
    :subtitle="exam ? exam.question_bank?.judul : 'Menunggu asesmen'"
  >
    <div class="max-w-2xl mx-auto w-full pb-20">
      
      <!-- Jika tidak ada ujian aktif -->
      <div v-if="!exam" class="rounded-2xl border border-white/10 p-10 flex flex-col items-center justify-center text-center space-y-4 shadow-xl" style="background: var(--card)">
        <div class="w-20 h-20 bg-indigo-500/10 rounded-full flex items-center justify-center text-4xl mb-2 animate-bounce">
          ⏳
        </div>
        <h2 class="text-xl font-black text-white">Belum Ada Ujian</h2>
        <p class="text-sm text-slate-400">Tunggu aba-aba dari Guru untuk memulai ujian. Layar ini akan otomatis menampilkan soal jika ujian diluncurkan.</p>
        <button @click="fetchExam" class="mt-4 px-6 py-2 rounded-full text-xs font-bold bg-white/5 hover:bg-white/10 border border-white/10 transition-colors">
          Refresh Manual
        </button>
      </div>

      <!-- Lembar Kerja Siswa -->
      <div v-else class="space-y-6">
        
        <!-- Progress Bar -->
        <div class="sticky top-0 z-10 pt-4 pb-2 bg-[#0B0F19]/90 backdrop-blur-md">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-slate-400">Progres Pengerjaan</span>
            <span class="text-xs font-bold text-indigo-400">{{ answeredCount }} / {{ questions.length }} Terjawab</span>
          </div>
          <div class="h-1.5 rounded-full bg-white/10 overflow-hidden">
            <div class="h-full bg-indigo-500 transition-all duration-300" :style="`width: ${(answeredCount / questions.length) * 100}%`"></div>
          </div>
        </div>

        <!-- Butir Soal -->
        <div v-for="(q, index) in questions" :key="q.id_question" class="rounded-2xl border border-white/10 p-5 space-y-5" style="background: var(--card)">
          <div class="flex gap-3">
            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-sm font-bold text-slate-300">
              {{ index + 1 }}
            </div>
            <div class="pt-1">
              <p class="text-sm text-white leading-relaxed">{{ q.pertanyaan }}</p>
            </div>
          </div>

          <!-- Opsi Jawaban -->
          <div class="pl-11 space-y-2">
            <label v-for="opt in ['A','B','C','D','E']" :key="opt" 
                   class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all duration-200"
                   :class="myAnswers[q.id_question] === opt ? 'bg-indigo-500/10 border-indigo-500/50 shadow-[inset_0_0_15px_rgba(79,70,229,0.1)]' : 'bg-black/20 border-white/5 hover:border-white/20 hover:bg-black/40'">
              <div class="pt-0.5">
                <input type="radio" :name="`q_${q.id_question}`" :value="opt" 
                       v-model="myAnswers[q.id_question]" 
                       @change="submitAnswer(q.id_question, opt)"
                       class="w-4 h-4 text-indigo-500 bg-transparent border-white/20 focus:ring-0 focus:ring-offset-0">
              </div>
              <div class="flex-1 text-sm">
                <span class="font-bold text-slate-400 mr-2">{{ opt }}.</span>
                <span :class="myAnswers[q.id_question] === opt ? 'text-white font-medium' : 'text-slate-300'">{{ q[`opsi_${opt.toLowerCase()}`] }}</span>
              </div>
            </label>
          </div>
        </div>

        <div v-if="answeredCount === questions.length" class="text-center py-8">
          <div class="text-4xl mb-4">🎉</div>
          <h3 class="text-lg font-bold text-white mb-2">Semua Soal Terjawab!</h3>
          <p class="text-sm text-slate-400">Silakan koreksi kembali jawaban Anda. Ujian akan tertutup otomatis ketika Guru mengakhirinya.</p>
        </div>

      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

const exam = ref(null);
const questions = ref([]);
const myAnswers = ref({});

let polling;

const fetchExam = async () => {
  try {
    const res = await axios.get('/siswa/ujian-live/fetch');
    if (res.data.exam) {
      exam.value = res.data.exam;
      questions.value = res.data.exam.question_bank?.questions || [];
      myAnswers.value = res.data.answered || {};
    } else {
      exam.value = null;
    }
  } catch (e) {
    console.error('Gagal mengambil data ujian');
  }
};

const submitAnswer = async (id_question, jawaban_siswa) => {
  try {
    await axios.post('/siswa/ujian-live/submit', {
      id_exam: exam.value.id_exam,
      id_question: id_question,
      jawaban_siswa: jawaban_siswa
    });
  } catch (e) {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.response?.data?.message || 'Gagal menyimpan jawaban. Cek koneksi Anda.', type: 'error' } }));
  }
};

const answeredCount = computed(() => Object.keys(myAnswers.value).length);

onMounted(() => {
  fetchExam();
  // Poll setiap 5 detik untuk cek jika ada ujian baru (jika saat ini null)
  polling = setInterval(() => {
    if (!exam.value) fetchExam();
  }, 5000);
});

onUnmounted(() => {
  if (polling) clearInterval(polling);
});
</script>
