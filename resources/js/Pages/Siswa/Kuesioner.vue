<template>
  <Head title="Kuesioner Kinerja" />

  <AppLayout
    title="Evaluasi Pendidik"
    subtitle="Berikan penilaian jujur Anda untuk membantu meningkatkan kualitas sekolah"
    :navigation="navigation"
  >
    <div class="max-w-3xl mx-auto space-y-6">
      
      <!-- Belum Ada Kuesioner -->
      <div v-if="!questionnaire" class="text-center p-10 border border-white/10 rounded-2xl" style="background: var(--card)">
        <div class="text-5xl mb-4">🌟</div>
        <h2 class="text-xl font-bold text-white mb-2">Belum Ada Evaluasi Aktif</h2>
        <p class="text-sm text-slate-400">Saat ini tidak ada kuesioner yang perlu Anda isi. Evaluasi biasanya dilakukan pada pertengahan atau akhir semester.</p>
      </div>

      <!-- Kuesioner Aktif -->
      <div v-else>
        <div class="mb-6 p-5 rounded-2xl border border-indigo-500/30 bg-indigo-500/10">
          <h2 class="text-lg font-black text-white mb-2">{{ questionnaire.judul }}</h2>
          <p class="text-sm text-indigo-200">{{ questionnaire.deskripsi }}</p>
          <div class="mt-4 inline-block px-3 py-1 bg-red-500/20 text-red-400 text-xs font-bold rounded-lg border border-red-500/30">
            ⚠️ Komentar Anda bersifat ANONIM bagi Guru yang bersangkutan.
          </div>
        </div>

        <div v-if="targets.length === 0" class="text-center p-8 border border-white/10 rounded-2xl text-slate-400">
          Tidak ada daftar guru yang sesuai dengan target evaluasi untuk kelas Anda.
        </div>

        <div v-else class="space-y-4">
          <!-- List Target Guru -->
          <div v-for="guru in targets" :key="guru.id_guru" 
               class="p-5 rounded-2xl border transition-all"
               :class="guru.sudah_dinilai ? 'border-green-500/30 bg-green-500/5' : 'border-white/10 bg-[#0B0F19] hover:border-indigo-500/50'">
            
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg"
                     :class="guru.sudah_dinilai ? 'bg-green-500/20 text-green-400' : 'bg-white/10 text-white'">
                  {{ guru.nama_guru.charAt(0) }}
                </div>
                <div>
                  <div class="font-bold text-white">{{ guru.nama_guru }}</div>
                  <div class="text-xs text-slate-400">{{ questionnaire.tipe === 'WALI_KELAS' ? 'Wali Kelas' : 'Guru Mata Pelajaran' }}</div>
                </div>
              </div>

              <div v-if="guru.sudah_dinilai" class="px-4 py-2 bg-green-500/10 text-green-400 text-xs font-bold rounded-xl border border-green-500/20 flex items-center gap-2">
                <span>✅</span> Selesai Dinilai
              </div>
              <button v-else @click="startEvaluate(guru)" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-500/20 transition-all hover:-translate-y-0.5">
                Mulai Penilaian
              </button>
            </div>
          </div>
        </div>

      </div>

      <!-- Form Penilaian (Modal) -->
      <div v-if="selectedTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
        <div class="w-full max-w-2xl bg-[#0B0F19] border border-white/10 rounded-2xl p-0 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
          
          <div class="p-6 border-b border-white/10 bg-white/5">
            <h3 class="font-bold text-white text-lg">Evaluasi: {{ selectedTarget.nama_guru }}</h3>
            <p class="text-xs text-slate-400 mt-1">Silakan berikan rating 1 (Sangat Kurang) hingga 5 (Sangat Baik).</p>
          </div>

          <div class="p-6 overflow-y-auto flex-1 space-y-6">
            <div v-for="q in questionnaire.questions" :key="q.id_q_question" class="space-y-3">
              <p class="text-sm font-semibold text-white">{{ q.pertanyaan }}</p>
              <div class="flex items-center gap-2">
                <button v-for="star in 5" :key="star" type="button"
                        @click="setRating(q.id_q_question, star)"
                        @mouseenter="hoverRating[q.id_q_question] = star"
                        @mouseleave="hoverRating[q.id_q_question] = 0"
                        class="text-3xl transition-transform hover:scale-110 focus:outline-none"
                        :class="star <= (hoverRating[q.id_q_question] || form.responses[q.id_q_question] || 0) ? 'text-yellow-400' : 'text-white/10'">
                  ★
                </button>
                <span class="ml-3 text-xs font-bold" 
                      :class="form.responses[q.id_q_question] ? 'text-indigo-400' : 'text-slate-500'">
                  {{ form.responses[q.id_q_question] ? form.responses[q.id_q_question] + '/5' : 'Belum dinilai' }}
                </span>
              </div>
            </div>

            <div class="pt-4 border-t border-white/10">
              <label class="text-sm font-semibold text-white block mb-2">Kritik & Saran (Opsional)</label>
              <textarea v-model="form.komentar" rows="3" placeholder="Sampaikan saran membangun secara jujur..." 
                        class="w-full bg-black/50 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
              <p class="text-[10px] text-slate-500 mt-2">Nama Anda tidak akan ditampilkan kepada guru yang bersangkutan.</p>
            </div>
          </div>

          <div class="p-6 border-t border-white/10 bg-white/5 flex justify-end gap-3">
            <button @click="selectedTarget = null" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-300 hover:bg-white/10 transition-colors">Batal</button>
            <button @click="submit" :disabled="!isFormValid || processing" 
                    class="px-8 py-2.5 rounded-xl text-sm font-bold text-white transition-all disabled:opacity-50"
                    :class="isFormValid ? 'bg-gradient-to-r from-green-500 to-emerald-600 shadow-lg shadow-green-500/20 hover:-translate-y-0.5' : 'bg-slate-700'">
              {{ processing ? 'Menyimpan...' : 'Kirim Penilaian' }}
            </button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  questionnaire: Object,
  targets: Array
});

const navigation = [
  {
    label: 'Akademik',
    items: [
      { href: '/siswa/dashboard', icon: '🏠', label: 'Beranda' },
      { href: '/siswa/scan-qr', icon: '📷', label: 'Scan QR Presensi', badge: 'Live' },
      { href: '/siswa/materi', icon: '📁', label: 'Materi Belajar' },
      { href: '/siswa/jadwal', icon: '📅', label: 'Jadwal Kelas' },
    ],
  },
  {
    label: 'Laporan',
    items: [
      { href: '/siswa/nilai', icon: '📊', label: 'Nilai & Capaian' },
      { href: '/siswa/kuesioner', icon: '📝', label: 'Evaluasi Pembelajaran' },
    ],
  },
];

const selectedTarget = ref(null);
const hoverRating = ref({});
const processing = ref(false);

const form = ref({
  responses: {},
  komentar: ''
});

const startEvaluate = (guru) => {
  selectedTarget.value = guru;
  form.value = {
    responses: {},
    komentar: ''
  };
  hoverRating.value = {};
};

const setRating = (qId, star) => {
  form.value.responses[qId] = star;
};

const isFormValid = computed(() => {
  if (!props.questionnaire) return false;
  // Pastikan semua pertanyaan sudah diberi rating
  const answeredCount = Object.keys(form.value.responses).length;
  return answeredCount === props.questionnaire.questions.length;
});

const submit = () => {
  processing.value = true;
  router.post('/siswa/kuesioner', {
    id_questionnaire: props.questionnaire.id_questionnaire,
    id_guru_target: selectedTarget.value.id_guru,
    responses: form.value.responses,
    komentar: form.value.komentar
  }, {
    preserveScroll: true,
    onSuccess: () => {
      selectedTarget.value = null;
      processing.value = false;
    },
    onError: () => {
      processing.value = false;
    }
  });
};
</script>
