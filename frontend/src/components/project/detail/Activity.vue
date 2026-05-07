<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../../api/axios';

const props = defineProps(['project']);
const emit = defineEmits(['refresh']);
const route = useRoute();

// ==========================================
// 1. IDENTITAS USER & DATA STATIS
// ==========================================
const currentUser = ref({
  id: 1,
  name: 'Kholid Superadmin',
  avatar: 'avatars/AtEyLvrVWA5Jfns8gaKQCCn1UUW08wuIiZn1Vkpg.png'
});

const getImageUrl = (path: string) => {
  if (!path) return '';
  return path.startsWith('http') ? path : `http://localhost:8000/uploads/${path}`;
};

const isVideo = (filename: string) => {
  if (!filename) return false;
  return ['mp4', 'mov', 'avi'].includes(filename.split('.').pop()?.toLowerCase() || '');
};

// ==========================================
// 2. STATE MANAGEMENT
// ==========================================
const showAddModal = ref(false);
const isLoadingPost = ref(false);
const commentInputs = ref<Record<number, string>>({});
const locations = ref<any[]>([]);

// State form aktivitas baru
const newTask = ref({
  name: '',
  description: '',
  work_order_id: null as number | null,
  location_id: null as number | null,
  file: null as File | null // Mendukung File atau Null
});

// ==========================================
// 3. API & LOGIC
// ==========================================
onMounted(async () => {
  try {
    const res = await api.get('/master-data/locations');
    locations.value = res.data;
  } catch (e) { console.error(e); }
});

const formatDate = (dateStr: string) => {
  if (!dateStr) return 'Baru';
  return new Date(dateStr).toLocaleDateString('id-ID', { month: 'short', day: '2-digit' });
};

// --- FIX ERROR MERAH TYPESCRIPT ---
const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    // Gunakan || null untuk memastikan jika tidak ada file, nilainya bukan undefined
    newTask.value.file = target.files[0] || null; 
  } else {
    newTask.value.file = null;
  }
};

const handleToggleLike = async (task: any) => {
  try {
    const res = await api.post(`/tasks/${task.id}/like`);
    if (res.data.status === 'liked') {
      task.likes_count++;
      task.is_liked_by_me = true;
    } else {
      task.likes_count--;
      task.is_liked_by_me = false;
    }
  } catch (e) { console.error(e); }
};

const handlePostComment = async (task: any) => {
  const body = commentInputs.value[task.id];
  if (!body?.trim()) return;
  try {
    const response = await api.post(`/tasks/${task.id}/comment`, { body });
    if (!task.comments) task.comments = []; // Proteksi agar tidak error unshift
    task.comments.unshift(response.data);
    task.comments_count++;
    commentInputs.value[task.id] = '';
  } catch (e) { console.error(e); }
};

const handleAddTask = async () => {
  if (!newTask.value.name) return;
  isLoadingPost.value = true;
  const formData = new FormData();
  formData.append('project_id', route.params.id as string);
  formData.append('task_name', newTask.value.name);
  formData.append('description', newTask.value.description);
  
  if (newTask.value.work_order_id) formData.append('work_order_id', String(newTask.value.work_order_id));
  if (newTask.value.location_id) formData.append('location_id', String(newTask.value.location_id));
  if (newTask.value.file) formData.append('document', newTask.value.file);

  try {
    await api.post('/project-tasks', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
    newTask.value = { name: '', description: '', work_order_id: null, location_id: null, file: null };
    showAddModal.value = false;
    emit('refresh');
  } catch (e) { console.error(e); } 
  finally { isLoadingPost.value = false; }
};
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500 relative">
    
    <div @click="showAddModal = true" class="bg-white border border-slate-200 p-4 rounded-[1.5rem] shadow-sm flex items-center gap-3 cursor-pointer hover:bg-slate-50 transition-colors">
      <div class="w-10 h-10 rounded-full overflow-hidden bg-indigo-100 border border-slate-200 shrink-0">
        <img :src="getImageUrl(currentUser.avatar)" class="w-full h-full object-cover">
      </div>
      <div class="flex-1 bg-slate-100 hover:bg-slate-200 transition-colors text-slate-500 text-[11px] font-bold px-5 py-3 rounded-full uppercase tracking-tight">
        Apa aktivitas hari ini, {{ currentUser.name.split(' ')[0] }}?
      </div>
      <i class="fas fa-video text-blue-500 text-lg mr-2"></i>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
      <div v-if="!project?.tasks?.length" class="col-span-full py-20 text-center grayscale opacity-30">
        <i class="fas fa-photo-video text-5xl text-slate-300"></i>
        <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mt-4">Belum ada dokumentasi</p>
      </div>
      
      <div v-for="task in project?.tasks" :key="task.id" class="bg-white border border-slate-100 rounded-[2rem] shadow-sm overflow-hidden flex flex-col h-full animate-in slide-in-from-bottom-5 transition-all hover:border-blue-200">
        
        <div class="p-4 flex items-center gap-3 bg-slate-50/50 border-b border-slate-50">
          <div class="w-7 h-7 rounded-full overflow-hidden border border-slate-200">
            <img :src="getImageUrl(currentUser.avatar)" class="w-full h-full object-cover">
          </div>
          <div class="min-w-0">
            <div class="text-[10px] font-black text-slate-800 uppercase truncate">{{ currentUser.name }}</div>
            <div class="text-[7px] font-bold text-slate-400 uppercase"><i class="fas fa-map-marker-alt"></i> {{ task.location_id || 'Area' }}</div>
          </div>
          <div class="ml-auto text-[8px] font-black text-slate-300 italic">{{ formatDate(task.created_at) }}</div>
        </div>

        <div v-if="task.document" class="aspect-square bg-slate-900 overflow-hidden relative">
          <video v-if="isVideo(task.document)" class="w-full h-full object-cover" controls>
            <source :src="getImageUrl(task.document)" type="video/mp4">
          </video>
          <img v-else :src="getImageUrl(task.document)" class="w-full h-full object-cover">
          <span v-if="task.work_order_id" class="absolute bottom-2 left-2 bg-amber-500/90 text-white text-[6px] px-1.5 py-0.5 rounded font-black uppercase shadow">#WO-{{ task.work_order_id }}</span>
        </div>

        <div class="p-4 flex-1">
          <h4 class="text-[11px] font-black text-slate-900 uppercase tracking-tight line-clamp-1 mb-1">{{ task.task_name }}</h4>
          <p class="text-[10px] font-medium text-slate-500 line-clamp-2 leading-relaxed">{{ task.description || 'Tidak ada deskripsi.' }}</p>
        </div>

        <div class="px-4 py-3 flex items-center gap-4 border-t border-slate-50">
          <button @click="handleToggleLike(task)" class="flex items-center gap-1.5 group">
            <i class="fa-heart text-xl transition-all" :class="task.is_liked_by_me ? 'fas text-rose-500' : 'far text-slate-300 group-hover:text-rose-400'"></i>
            <span class="text-[10px] font-black text-slate-500">{{ task.likes_count || 0 }}</span>
          </button>
          <button class="flex items-center gap-1.5 group">
            <i class="far fa-comment text-xl text-slate-300 group-hover:text-blue-500"></i>
            <span class="text-[10px] font-black text-slate-500">{{ task.comments_count || 0 }}</span>
          </button>
        </div>

        <div v-if="task.comments?.length" class="px-4 pb-3 space-y-2 max-h-24 overflow-y-auto bg-slate-50/30 pt-2 custom-scrollbar">
          <div v-for="c in task.comments" :key="c.id" class="flex gap-2 items-start">
            <div class="w-5 h-5 rounded-full overflow-hidden shrink-0 border border-white shadow-sm">
               <img :src="getImageUrl(c.avatar || 'avatars/default.png')" class="w-full h-full object-cover">
            </div>
            <div class="min-w-0">
               <span class="text-[8px] font-black text-slate-800 uppercase italic mr-1">{{ c.name.split(' ')[0] }}</span>
               <span class="text-[9px] text-slate-600 leading-tight">{{ c.body }}</span>
            </div>
          </div>
        </div>

        <div class="p-3 border-t border-slate-100 bg-slate-50 flex items-center gap-2">
          <input v-model="commentInputs[task.id]" type="text" placeholder="Komen..." class="flex-1 bg-white border border-slate-200 rounded-full text-[10px] px-3 py-1.5 outline-none focus:ring-1 focus:ring-blue-100 transition-all">
          <button @click="handlePostComment(task)" :disabled="!commentInputs[task.id]" class="text-blue-600 disabled:opacity-20"><i class="fas fa-paper-plane"></i></button>
        </div>
      </div>
    </div>

    <div v-if="showAddModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-lg rounded-[2rem] shadow-2xl overflow-hidden animate-in zoom-in duration-300">
        <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
          <h3 class="text-xs font-black text-slate-800 uppercase italic tracking-widest">Posting Dokumentasi</h3>
          <button @click="showAddModal = false" class="text-slate-400 hover:text-rose-500"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6 space-y-6">
          <div class="flex items-center gap-4">
            <img :src="getImageUrl(currentUser.avatar)" class="w-12 h-12 rounded-full border-2 border-indigo-100 object-cover">
            <div>
              <div class="text-xs font-black text-slate-800 uppercase italic">{{ currentUser.name }}</div>
              <div class="text-[9px] font-bold text-indigo-500 uppercase">Update Progress</div>
            </div>
          </div>
          <input v-model="newTask.name" type="text" placeholder="Judul dokumentasi / aktivitas..." class="w-full text-xl font-black text-slate-800 outline-none border-none focus:ring-0">
          <textarea v-model="newTask.description" rows="3" placeholder="Apa detail yang Anda kerjakan? (Opsional)..." class="w-full text-xs text-slate-500 outline-none border-none focus:ring-0 resize-none"></textarea>
          
          <div class="border border-slate-200 rounded-2xl p-4 flex items-center gap-4 overflow-x-auto no-scrollbar">
            <span class="text-[9px] font-black text-slate-400 uppercase shrink-0">Hubungkan:</span>
            <div class="flex items-center gap-4 ml-auto">
               <div class="flex items-center gap-2 shrink-0">
                  <i class="fas fa-clipboard-list text-slate-400"></i>
                  <select v-model="newTask.work_order_id" class="bg-slate-50 border border-slate-200 rounded-xl px-2 py-1 text-[9px] font-bold uppercase outline-none">
                    <option :value="null">TANPA WO</option>
                    <option v-for="wo in project?.work_orders" :key="wo.id" :value="wo.id">#WO-{{ wo.id }}</option>
                  </select>
               </div>
               <div class="flex items-center gap-2 shrink-0">
                  <i class="fas fa-map-marker-alt text-slate-400"></i>
                  <select v-model="newTask.location_id" class="bg-slate-50 border border-emerald-200 rounded-xl px-2 py-1 text-[9px] font-bold uppercase outline-none">
                    <option :value="null">TANPA LOKASI</option>
                    <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name }}</option>
                  </select>
               </div>
               <label class="cursor-pointer text-blue-500 shrink-0">
                 <i class="fas fa-photo-video text-lg"></i>
                 <input type="file" @change="handleFileChange" accept="image/*,video/*" class="hidden">
               </label>
            </div>
          </div>
          <div v-if="newTask.file" class="bg-blue-50 text-blue-600 px-3 py-2 rounded-xl text-[9px] font-black flex items-center gap-2">
             <i class="fas fa-paperclip"></i> <span class="truncate">{{ newTask.file.name }}</span>
             <button @click="newTask.file = null" class="ml-auto text-rose-500"><i class="fas fa-times"></i></button>
          </div>
        </div>
        <div class="p-6 bg-slate-50 border-t border-slate-100">
          <button @click="handleAddTask" :disabled="!newTask.name || isLoadingPost" class="w-full py-4 rounded-2xl bg-blue-600 text-white text-[11px] font-black uppercase tracking-[0.2em] shadow-lg hover:bg-blue-700 transition-all flex items-center justify-center">
            <i v-if="isLoadingPost" class="fas fa-spinner fa-spin mr-2"></i> Posting Dokumentasi
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e2e8f0; border-radius: 10px; }
.animate-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>