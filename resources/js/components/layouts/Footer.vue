<template>
    <footer class="footer">
        <strong>Copyright &copy; {{ new Date().getFullYear() }}
            <a href="javascript:" title="Advance Management System" target="_blank">{{projectName}}</a></strong>
        Developed by MIS
    </footer>
</template>
<script setup>
import {projectName} from "../../base_url";
</script>