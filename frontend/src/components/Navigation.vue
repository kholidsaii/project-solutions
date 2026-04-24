<script setup lang="ts">
import { useRouter } from 'vue-router';

const router = useRouter();

const logout = () => {
  localStorage.clear();
  router.push('/login');
};
</script>

<template>
  <nav class="fixed left-2 md:left-4 top-10 w-[60px] md:w-[70px] bg-white rounded-[2.5rem] shadow-xl py-6 flex flex-col items-center z-[200] border border-slate-50 transition-all">
    
    <router-link to="/" class="sidebar-item group" active-class="active">
      <span class="label text-[7px] md:text-[9px]">Home</span>
      <div class="icon-box w-9 h-9 md:w-11 md:h-11">
        <img src="/logo-sidebar-1.png" alt="Home" class="w-full h-full object-contain p-2" />
      </div>
    </router-link>

    <router-link to="/activity" class="sidebar-item group" active-class="active">
      <span class="label text-[7px] md:text-[9px]">Aktivity</span>
      <div class="icon-box w-9 h-9 md:w-11 md:h-11">
         <img src="/logo-sidebar-2.png" alt="Activity" class="w-full h-full object-contain p-2" />
      </div>
    </router-link>

    <router-link to="/analysis" class="sidebar-item group mb-4" active-class="active">
      <span class="label text-[7px] md:text-[9px]">Analysis</span>
      <div class="icon-box w-9 h-9 md:w-11 md:h-11">
         <img src="/logo-sidebar-3.png" alt="Analysis" class="w-full h-full object-contain p-2" />
      </div>
    </router-link>

    <div class="flex flex-col items-center gap-4 mt-auto">
       <div class="w-9 h-9 md:w-11 md:h-11 rounded-full overflow-hidden shadow-sm border border-slate-100">
         <img src="https://ui-avatars.com/api/?name=Kholid+Saifullah&background=444&color=fff" alt="User" class="w-full h-full object-cover">
       </div>
       <button @click="logout" class="text-slate-200 hover:text-red-500 transition-all">
         <i class="fas fa-power-off text-base md:text-lg"></i>
       </button>
    </div>
  </nav>
</template>

<style scoped>
@reference "../style.css";

.sidebar-item {
  @apply flex flex-col items-center w-full mb-4 cursor-pointer transition-all;
}

.label {
  @apply text-[9px] font-bold text-slate-400 mb-1 uppercase tracking-tighter;
}

.icon-box {
  /* Box icon sekarang punya ukuran tetap agar gambar di dalamnya bisa di tengah */
  @apply w-11 h-11 flex items-center justify-center rounded-2xl transition-all duration-300 shadow-sm overflow-hidden;
}

/* State Active */
.active .label {
  @apply text-slate-800;
}

.active .icon-box {
  @apply shadow-lg scale-110 ring-2 ring-white;
}

/* State Hover */
.sidebar-item:hover .icon-box {
  @apply brightness-110 shadow-md -translate-y-0.5;
}

/* HAPUS BAGIAN FILTER INVERT AGAR GAMBAR TIDAK JADI PUTIH */
</style>