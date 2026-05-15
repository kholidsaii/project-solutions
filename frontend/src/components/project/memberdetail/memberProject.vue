<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../../api/axios';

const props = defineProps(['memberData']);
const router = useRouter();
const userProjects = ref<any[]>([]);
const isLoading = ref(true);

onMounted(async () => {
  try {
    // Idealnya backend menyediakan endpoint: /projects?user_id=1
    // Atau jika sudah di-load dari awal: userProjects.value = props.memberData.projects;
    const res = await api.get('/projects'); 
    // Filter sementara di frontend (jika backend belum memfilter)
    userProjects.value = res.data.data ? res.data.data.filter((p:any) => p.members?.includes(props.memberData.id)) : [];
  } catch (e) {
    console.error(e);
  } finally {
    isLoading.value = false;
  }
});

const goToProject = (id: number) => {
  router.push(`/projects/${id}`);
};
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500 w-full">
    
    <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
       <div class="flex items-center gap-4">
          <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center"><i class="fas fa-project-diagram"></i></div>
          <div>
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Assigned Projects</h3>
            <p class="text-[10px] text-slate-500 font-bold uppercase mt-0.5">Daftar project yang ditangani oleh {{ memberData?.name }}</p>
          </div>
       </div>
    </div>

    <div v-if="isLoading" class="p-12 text-center"><i class="fas fa-spinner fa-spin text-3xl text-slate-300"></i></div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="project in userProjects" :key="project.id" @click="goToProject(project.id)" class="bg-white border border-slate-200 rounded-[2rem] p-6 shadow-sm hover:border-indigo-300 hover:shadow-md transition-all cursor-pointer group">
        <div class="flex justify-between items-start mb-4">
           <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-500 transition-colors">
              <i class="fas fa-briefcase text-xl"></i>
           </div>
           <span class="px-3 py-1 rounded-full text-[9px] font-black bg-emerald-50 text-emerald-600 uppercase">{{ project.status || 'Active' }}</span>
        </div>
        <h4 class="text-xs font-black text-slate-800 uppercase mb-1">{{ project.project_title || 'Nama Project' }}</h4>
        <p class="text-[10px] font-bold text-slate-400 uppercase">ID-{{ project.id }} • {{ project.client_name || 'Internal' }}</p>
        
        <div class="mt-6 pt-4 border-t border-slate-50 flex justify-between items-center">
           <span class="text-[9px] font-black text-slate-500 uppercase">Progress</span>
           <span class="text-[10px] font-black text-indigo-600">{{ project.progress || 0 }}%</span>
        </div>
      </div>

      <div v-if="userProjects.length === 0" class="col-span-full py-20 text-center border-2 border-dashed border-slate-200 rounded-[2rem]">
         <i class="fas fa-folder-open text-4xl text-slate-200 mb-4"></i>
         <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Belum ditugaskan di project manapun.</p>
      </div>
    </div>
  </div>
</template>