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
  
  if (path.startsWith('http')) {
    return path.replace(/^http:\/\//i, 'https://'); 
  }
  
  // Ambil Base URL
  let baseUrl = import.meta.env.VITE_API_URL || 'https://kerjapro.com'; 
  
  // PERBAIKAN: Hapus "/api" di akhir URL jika ada, agar path gambar tidak salah sasaran
  baseUrl = baseUrl.replace(/\/api\/?$/, '');

  // Gabungkan URL yang sudah bersih dengan path gambar
  return `${baseUrl}/uploads/${path}`;
};

// --- LOGIC DETEKSI TIPE FILE ---
const isVideo = (filename: string) => {
  if (!filename) return false;
  return ['mp4', 'mov', 'avi', 'webm'].includes(filename.split('.').pop()?.toLowerCase() || '');
};

const isImage = (filename: string) => {
  if (!filename) return false;
  return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(filename.split('.').pop()?.toLowerCase() || '');
};

const getFileIcon = (filename: string) => {
  if (!filename) return 'fas fa-file';
  const ext = filename.split('.').pop()?.toLowerCase();
  if (ext === 'pdf') return 'fas fa-file-pdf text-red-500';
  if (['doc', 'docx'].includes(ext!)) return 'fas fa-file-word text-blue-600';
  if (['xls', 'xlsx'].includes(ext!)) return 'fas fa-file-excel text-emerald-600';
  if (['zip', 'rar'].includes(ext!)) return 'fas fa-file-archive text-amber-500';
  return 'fas fa-file-alt text-slate-400';
};

const getFileName = (path: string) => {
  if (!path) return '';
  return path.split('/').pop() || 'Dokumen';
};

// ==========================================
// 2. STATE MANAGEMENT
// ==========================================
const showAddModal = ref(false);
const isLoadingPost = ref(false);
const commentInputs = ref<Record<number, string>>({});
const locations = ref<any[]>([]);

const getLocationName = (id: number | null) => {
  if (!id) return 'Site Project'; // Default jika tidak ada lokasi
  const loc = locations.value.find((l: any) => l.id === id);
  return loc ? loc.name : 'Site Project';
};

const selectedTask = ref<any>(null);
const showDetailModal = ref(false);

const activeDropdown = ref<number | null>(null);

const showEditModal = ref(false);
const isEditing = ref(false);
const editTaskForm = ref({ 
  id: 0, 
  name: '', 
  description: '',
  file: null as File | null,
  current_file: ''
});

const newTask = ref({
  name: '',
  description: '',
  work_order_id: null as number | null,
  location_id: null as number | null,
  file: null as File | null
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
  return new Date(dateStr).toLocaleDateString('id-ID', { month: 'long', day: 'numeric', year: 'numeric' });
};

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    newTask.value.file = target.files[0] || null; 
  } else {
    newTask.value.file = null;
  }
};

// TAMBAHAN: Handler file untuk form Edit
const handleEditFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    editTaskForm.value.file = target.files[0] || null; 
  } else {
    editTaskForm.value.file = null;
  }
};

const toggleDropdown = (taskId: number) => {
  activeDropdown.value = activeDropdown.value === taskId ? null : taskId;
};

// --- FIX BUGS: LIKE DAN COMMENT ---
const handleToggleLike = async (task: any) => {
  try {
    const res = await api.post(`/tasks/${task.id}/like`);
    task.likes_count = Number(task.likes_count || 0);
    
    if (res.data.status === 'liked') {
      task.likes_count += 1;
      task.is_liked_by_me = true;
    } else {
      task.likes_count = Math.max(0, task.likes_count - 1); // Cegah nilai minus
      task.is_liked_by_me = false;
    }
  } catch (e) { console.error(e); }
};

const handlePostComment = async (task: any) => {
  const body = commentInputs.value[task.id];
  if (!body?.trim()) return;
  
  try {
    const response = await api.post(`/tasks/${task.id}/comment`, { body });
    if (!task.comments) task.comments = []; 
    
    // MAPPING DATA BACKEND KE FRONTEND AGAR TIDAK CRASH
    const newComment = {
      id: response.data.id,
      body: response.data.body,
      created_at: response.data.created_at,
      name: response.data.user?.name || currentUser.value.name,
      avatar: response.data.user?.avatar_url || currentUser.value.avatar
    };

    task.comments.push(newComment); 
    task.comments_count = task.comments.length; 
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

const handleDeleteTask = async (taskId: number) => {
  if (!confirm("Hapus aktivitas / dokumentasi ini? Data tidak bisa dikembalikan.")) return;
  activeDropdown.value = null; 
  try {
    await api.delete(`/project-tasks/${taskId}`);
    emit('refresh');
  } catch (e) { console.error("Gagal menghapus task", e); }
};

// PERBAIKAN: Tangkap task_name dan document saat buka modal
const openEditModal = (task: any) => {
  activeDropdown.value = null; 
  editTaskForm.value = { 
    id: task.id, 
    name: task.task_name || '', 
    description: task.description || '',
    file: null,
    current_file: task.document || ''
  };
  showEditModal.value = true;
};

// PERBAIKAN: Gunakan FormData & post dengan _method PUT
const handleEditTask = async () => {
  isEditing.value = true;
  
  const formData = new FormData();
  formData.append('_method', 'PUT'); // Trik Laravel untuk method spoofing
  formData.append('task_name', editTaskForm.value.name);
  formData.append('description', editTaskForm.value.description);
  
  // Apabila ada file baru yg diupload
  if (editTaskForm.value.file) {
    formData.append('document', editTaskForm.value.file);
  }

  try {
    await api.post(`/project-tasks/${editTaskForm.value.id}`, formData, { 
      headers: { 'Content-Type': 'multipart/form-data' } 
    });
    showEditModal.value = false;
    emit('refresh');
  } catch (e) { 
    console.error("Gagal update task", e); 
  } finally { 
    isEditing.value = false; 
  }
};

const openDetailModal = (task: any) => {
  selectedTask.value = task;
  showDetailModal.value = true;
  document.body.style.overflow = 'hidden'; 
};

const closeDetailModal = () => {
  showDetailModal.value = false;
  selectedTask.value = null;
  document.body.style.overflow = 'auto';
};
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500 relative" @click="activeDropdown = null">
    
    <div @click.stop="showAddModal = true" class="bg-white border border-slate-200 p-4 rounded-[1.5rem] shadow-sm flex items-center gap-3 cursor-pointer hover:bg-slate-50 transition-colors">
      <div class="w-10 h-10 rounded-full overflow-hidden bg-indigo-100 border border-slate-200 shrink-0">
        <img :src="getImageUrl(currentUser.avatar)" class="w-full h-full object-cover">
      </div>
      <div class="flex-1 bg-slate-100 hover:bg-slate-200 transition-colors text-slate-500 text-[11px] font-bold px-5 py-3 rounded-full uppercase tracking-tight">
        Apa aktivitas hari ini, {{ currentUser.name?.split(' ')[0] || 'Tim' }}?
      </div>
      <i class="fas fa-video text-blue-500 text-lg mr-2"></i>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mt-4 max-w-5xl mx-auto">
      <div v-if="!project?.tasks?.length" class="col-span-full py-20 text-center grayscale opacity-30">
        <i class="fas fa-photo-video text-5xl text-slate-300"></i>
        <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mt-4">Belum ada dokumentasi</p>
      </div>
      
      <div v-for="task in project?.tasks" :key="task.id" class="bg-white border border-slate-200 rounded-[1.5rem] shadow-sm overflow-hidden flex flex-col h-full relative">
        
        <div class="p-3.5 flex items-center gap-3 bg-white relative">
          <div class="w-8 h-8 rounded-full overflow-hidden border border-slate-200 shrink-0 cursor-pointer">
            <img :src="getImageUrl(currentUser.avatar)" class="w-full h-full object-cover">
          </div>
          <div class="min-w-0 flex-1">
            <div class="text-[11px] font-bold text-slate-900 uppercase truncate cursor-pointer">{{ currentUser.name }}</div>
            <div class="text-[9px] text-slate-500"><i class="fas fa-map-marker-alt text-rose-500 mr-1"></i> {{ getLocationName(task.location_id) }}</div>
          </div>
          
          <div class="relative">
            <button @click.stop="toggleDropdown(task.id)" class="text-slate-400 hover:text-slate-600 p-2">
              <i class="fas fa-ellipsis-h"></i>
            </button>
            <div v-if="activeDropdown === task.id" class="absolute right-0 top-full mt-1 bg-white border border-slate-100 shadow-xl rounded-xl py-2 w-32 z-[50] animate-in zoom-in-95 origin-top-right">
              <button @click.stop="openEditModal(task)" class="w-full text-left px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 flex items-center">
                <i class="fas fa-edit mr-2 text-blue-500"></i> Edit
              </button>
              <button @click.stop="handleDeleteTask(task.id)" class="w-full text-left px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 flex items-center">
                <i class="fas fa-trash-alt mr-2"></i> Hapus
              </button>
            </div>
          </div>
        </div>

        <div @click="openDetailModal(task)" class="aspect-square bg-slate-100 overflow-hidden relative border-y border-slate-100 cursor-pointer flex items-center justify-center group z-0">
          <template v-if="task.document">
            <video v-if="isVideo(task.document)" class="w-full h-full object-cover"><source :src="getImageUrl(task.document)" type="video/mp4"></video>
            <div v-else-if="isVideo(task.document)" class="absolute inset-0 flex items-center justify-center bg-black/20 text-white opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-play-circle text-5xl drop-shadow-md"></i></div>
            <img v-else-if="isImage(task.document)" :src="getImageUrl(task.document)" class="w-full h-full object-cover">
            <div v-else class="flex flex-col items-center text-slate-500">
               <i :class="getFileIcon(task.document)" class="text-5xl mb-3 drop-shadow-sm"></i>
               <span class="text-[10px] font-bold px-4 text-center line-clamp-1 bg-white px-2 py-1 rounded shadow-sm border border-slate-200">{{ getFileName(task.document) }}</span>
            </div>
          </template>
          <div v-else class="flex flex-col items-center justify-center text-slate-300">
            <div class="text-xs font-black uppercase text-slate-400 tracking-wider text-center px-6">{{ task.task_name }}</div>
          </div>
          <span v-if="task.work_order_id" class="absolute bottom-3 left-3 bg-indigo-600 text-white text-[9px] px-2 py-1 rounded font-bold uppercase shadow shadow-indigo-200">#WO-{{ task.work_order_id }}</span>
        </div>

        <div class="px-4 pt-3 pb-2 flex items-center gap-5">
          <button @click="handleToggleLike(task)" class="group hover:scale-110 transition-transform flex items-center gap-2">
            <i :class="task.is_liked_by_me ? 'fas fa-heart text-rose-500 text-2xl' : 'far fa-heart text-slate-800 text-2xl group-hover:text-slate-500'"></i>
            <span class="text-[13px] font-bold text-slate-800">{{ Math.max(0, task.likes_count || 0) }}</span>
          </button>
          <button @click="openDetailModal(task)" class="hover:scale-110 transition-transform flex items-center gap-2">
            <i class="far fa-comment text-slate-800 text-2xl hover:text-slate-500"></i>
            <span class="text-[13px] font-bold text-slate-800">{{ task.comments?.length || task.comments_count || 0 }}</span>
          </button>
        </div>

        <div class="px-4 pb-4">
          <div class="text-sm text-slate-800 leading-snug line-clamp-2 mt-1">
            <span class="font-bold text-xs uppercase mr-1">{{ currentUser.name?.split(' ')[0] }}</span> 
            <span class="text-[11px]">{{ task.description || task.task_name }}</span>
          </div>
          <button v-if="(task.comments?.length || task.comments_count) > 0" @click="openDetailModal(task)" class="text-slate-500 text-[11px] mt-1.5 font-medium hover:underline cursor-pointer">
            Lihat semua {{ task.comments?.length || task.comments_count }} komentar
          </button>
          <div class="text-[9px] font-medium text-slate-400 uppercase mt-1.5">{{ formatDate(task.created_at) }}</div>
        </div>

        <div class="px-4 py-3 border-t border-slate-100 flex items-center gap-2">
          <i class="far fa-smile text-slate-400 text-lg"></i>
          <input v-model="commentInputs[task.id]" @keyup.enter="handlePostComment(task)" type="text" placeholder="Tambahkan komentar..." class="flex-1 outline-none text-xs text-slate-800 placeholder:text-slate-400 bg-transparent">
          <button @click="handlePostComment(task)" :disabled="!commentInputs[task.id]" class="text-blue-500 font-bold text-xs disabled:opacity-40">Kirim</button>
        </div>
      </div>
    </div>

    <div v-if="showEditModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-lg rounded-[2rem] shadow-2xl overflow-hidden animate-in zoom-in duration-200">
        <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
          <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Edit Aktivitas</h3>
          <button @click="showEditModal = false" class="text-slate-400 hover:text-rose-500"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6 space-y-4">
          
          <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-2 block">Judul / Title</label>
            <input v-model="editTaskForm.name" type="text" placeholder="Judul aktivitas..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold text-slate-800 outline-none focus:border-blue-400">
          </div>

          <div>
            <label class="text-[10px] font-black text-slate-400 uppercase mb-2 block">Deskripsi / Caption</label>
            <textarea v-model="editTaskForm.description" rows="3" placeholder="Ubah deskripsi aktivitas..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-700 outline-none focus:border-blue-400 resize-none"></textarea>
          </div>

          <div>
             <label class="text-[10px] font-black text-slate-400 uppercase mb-2 block">Update Media / Dokumen (Opsional)</label>
             <div class="flex items-center gap-3">
               <label class="cursor-pointer bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-xl text-[10px] font-bold transition-colors shadow-sm">
                 <i class="fas fa-upload mr-2 text-blue-500"></i> Pilih File Baru
                 <input type="file" @change="handleEditFileChange" accept="image/*,video/*,application/pdf,.doc,.docx,.xls,.xlsx" class="hidden">
               </label>
               <div v-if="editTaskForm.file" class="text-[10px] font-bold text-blue-600 truncate flex-1"><i class="fas fa-check-circle mr-1"></i> {{ editTaskForm.file.name }}</div>
               <div v-else-if="editTaskForm.current_file" class="text-[10px] font-medium text-slate-500 truncate flex-1">File saat ini: <b>{{ getFileName(editTaskForm.current_file) }}</b></div>
             </div>
          </div>

        </div>
        <div class="p-5 bg-slate-50 border-t border-slate-100">
          <button @click="handleEditTask" :disabled="isEditing || !editTaskForm.name" class="w-full py-3 rounded-xl bg-blue-600 text-white text-[11px] font-black uppercase tracking-widest shadow-lg hover:bg-blue-700 transition-all disabled:opacity-50">
            <i v-if="isEditing" class="fas fa-spinner fa-spin mr-2"></i> Simpan Perubahan
          </button>
        </div>
      </div>
    </div>

    <div v-if="showDetailModal && selectedTask" class="fixed inset-0 z-[120] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 md:p-10 animate-in fade-in duration-200">
      <button @click="closeDetailModal" class="absolute top-4 right-4 md:top-6 md:right-8 text-white hover:text-slate-300 transition-colors z-[130]"><i class="fas fa-times text-3xl drop-shadow-md"></i></button>
      <div class="bg-white w-full max-w-6xl max-h-full rounded-md md:rounded-r-lg flex flex-col md:flex-row overflow-hidden shadow-2xl relative animate-in zoom-in-95 duration-300">
        
        <div class="w-full md:w-[55%] lg:w-[60%] bg-black flex items-center justify-center relative min-h-[30vh] md:min-h-[80vh]">
          <button @click="closeDetailModal" class="absolute top-3 right-3 text-white bg-black/50 w-8 h-8 rounded-full md:hidden flex items-center justify-center z-10"><i class="fas fa-times"></i></button>
          <template v-if="selectedTask.document">
            <video v-if="isVideo(selectedTask.document)" :src="getImageUrl(selectedTask.document)" controls autoplay class="max-h-[30vh] md:max-h-[85vh] max-w-full outline-none"></video>
            <img v-else-if="isImage(selectedTask.document)" :src="getImageUrl(selectedTask.document)" class="max-h-[30vh] md:max-h-[85vh] max-w-full object-contain">
            <div v-else class="text-white flex flex-col items-center bg-slate-900 w-full h-full justify-center">
              <i :class="getFileIcon(selectedTask.document)" class="text-6xl md:text-8xl mb-4 drop-shadow-lg"></i>
              <span class="text-xs md:text-sm font-bold opacity-80">{{ getFileName(selectedTask.document) }}</span>
              <a :href="getImageUrl(selectedTask.document)" target="_blank" download class="mt-6 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 transition-colors rounded-full text-xs font-bold uppercase tracking-widest shadow-lg"><i class="fas fa-download mr-1"></i> Unduh File</a>
            </div>
          </template>
          <div v-else class="text-slate-600 flex flex-col items-center justify-center bg-slate-900 w-full h-full">
            <h2 class="text-xl md:text-2xl font-black text-slate-700 uppercase tracking-widest text-center px-10">{{ selectedTask.task_name }}</h2>
            <p class="text-xs text-slate-500 mt-2"><i class="fas fa-tasks mr-1"></i> Aktivitas Tanpa Media Lampiran</p>
          </div>
        </div>

        <div class="w-full md:w-[45%] lg:w-[40%] bg-white flex flex-col h-[50vh] md:h-auto md:max-h-[85vh]">
          <div class="p-4 border-b border-slate-100 flex items-center gap-3 bg-white shrink-0">
            <img :src="getImageUrl(currentUser.avatar)" class="w-8 h-8 rounded-full border border-slate-200">
            <div class="text-xs font-bold text-slate-900 uppercase tracking-tight">{{ currentUser.name }}</div>
            <div v-if="selectedTask.work_order_id" class="ml-auto text-[9px] font-black text-indigo-600 bg-indigo-50 px-2 py-1 rounded uppercase">#WO-{{ selectedTask.work_order_id }}</div>
          </div>

          <div class="flex-1 overflow-y-auto p-4 custom-scrollbar space-y-4 bg-white">
            <div class="flex gap-3">
              <img :src="getImageUrl(currentUser.avatar)" class="w-8 h-8 rounded-full shrink-0 border border-slate-100 mt-0.5">
              <div class="text-sm text-slate-800 leading-snug">
                <span class="font-bold text-xs uppercase mr-2 text-slate-900">{{ currentUser.name?.split(' ')[0] }}</span>
                <span>{{ selectedTask.description || selectedTask.task_name }}</span>
                <div class="text-[9px] text-slate-400 mt-2 uppercase font-medium">{{ formatDate(selectedTask.created_at) }}</div>
              </div>
            </div>

            <div v-for="c in selectedTask.comments" :key="c.id" class="flex gap-3 mt-4 group">
              <img :src="getImageUrl(c.avatar || 'avatars/default.png')" class="w-8 h-8 rounded-full shrink-0 border border-slate-100 mt-0.5">
              <div class="text-sm text-slate-800 leading-snug">
                <span class="font-bold text-xs uppercase mr-2 text-slate-900">{{ c.name?.split(' ')[0] || 'Tim' }}</span>
                <span>{{ c.body }}</span>
                <div class="text-[9px] text-slate-400 mt-1 uppercase flex items-center gap-3"><span>{{ formatDate(c.created_at) }}</span></div>
              </div>
            </div>
          </div>

          <div class="p-4 border-t border-slate-100 bg-white shrink-0 shadow-[0_-10px_20px_-15px_rgba(0,0,0,0.1)]">
            <div class="flex items-center gap-5 mb-3">
              <button @click="handleToggleLike(selectedTask)" class="group hover:scale-110 transition-transform flex items-center gap-2">
                <i :class="selectedTask.is_liked_by_me ? 'fas fa-heart text-rose-500 text-2xl' : 'far fa-heart text-slate-800 text-2xl group-hover:text-slate-500'"></i>
                <span class="text-[13px] font-bold text-slate-800">{{ Math.max(0, selectedTask.likes_count || 0) }}</span>
              </button>
              <div class="flex items-center gap-2">
                <i class="far fa-comment text-slate-800 text-2xl"></i>
                <span class="text-[13px] font-bold text-slate-800">{{ selectedTask.comments?.length || selectedTask.comments_count || 0 }}</span>
              </div>
            </div>
            <div class="text-[10px] text-slate-400 uppercase mb-4">{{ formatDate(selectedTask.created_at) }}</div>

            <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
              <i class="far fa-smile text-slate-400 text-lg"></i>
              <input v-model="commentInputs[selectedTask.id]" @keyup.enter="handlePostComment(selectedTask)" type="text" placeholder="Tambahkan komentar..." class="flex-1 outline-none text-xs text-slate-800 placeholder:text-slate-400 bg-transparent py-1">
              <button @click="handlePostComment(selectedTask)" :disabled="!commentInputs[selectedTask.id]" class="text-blue-500 font-bold text-xs disabled:opacity-40 transition-opacity">Kirim</button>
            </div>
          </div>
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
                    <option v-for="wo in project?.work_orders" :key="wo.id" :value="wo.id">#WO-{{ wo.title }}</option>
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
                 <input type="file" @change="handleFileChange" accept="image/*,video/*,application/pdf,.doc,.docx,.xls,.xlsx" class="hidden">
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
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
.animate-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>