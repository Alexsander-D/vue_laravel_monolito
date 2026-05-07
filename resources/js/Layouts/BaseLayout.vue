<script setup>
import { Head } from "@inertiajs/vue3";
import SoloHeader from "@/Components/SoloHeader.vue";
// import Sidebar from "@/Components/Sidebar.vue";
import ContentPage from "@/Components/ContentPage.vue";
import Footer from "@/Components/Footer.vue";
import { defineProps } from "vue";
const props = defineProps({
  title: {
    type: String,
    default: "Base Layout",
  },
});
</script>
<template>
  <div>
    <Head :title="title" />
    <!-- ========== HEADER ========== -->
    <SoloHeader />
    <!-- ========== END HEADER ========== -->

    <!-- ========== SIDEBAR ========== -->
    <!-- <Sidebar /> -->
    <!-- ========== END SIDEBAR ========== -->

    <!-- ========== CONTENT ========== -->
    <ContentPage>
      <slot></slot>
    </ContentPage>
    <!-- ========== END MAIN CONTENT ========== -->
    <!-- ========== FOOTER ========== -->
    <Footer />
    <!-- ========== END FOOTER ========== -->
  </div>
</template>
