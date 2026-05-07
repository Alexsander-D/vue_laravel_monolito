<script setup>
import Datatable from '@/Components/Datatable.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    materialsData: {
        type: Object,
        required: true,
        default: () => [],
    },
});

const tableHeaders = ref([
    { name: "Identificação (CPF/CNPJ)" },
    { name: "Razão Social" },
    { name: "Nome Fantasia" },
    { name: "CEP" },
    { name: "Estado" },
    { name: "Cidade" },
    { name: "Rua" },
    { name: "Bairro" },
    { name: "Número" },
    { name: "Telefone" },
    { name: "E-mail" },
]);

const tableData = computed(() => {
    if (!props.materialsData.customersInfo || props.materialsData.customersInfo.length === 0) {
        return [];
    }

    const customer = props.materialsData.customersInfo[0];

    return [
        {
            type_person: customer.type_person,
            company_name: customer.company_name,
            trade_name: customer.trade_name,
            cep: customer.cep,
            state: customer.state,
            city: customer.city,
            road: customer.road,
            district: customer.district,
            number: customer.number,
            telephone: customer.telephone,
            email: customer.email,
        },
    ];
});

const productTableHeaders = ref([
    { name: "Família" },
    { name: "Produto" },
    { name: "Total" },
    { name: "Recuperado" },
    { name: "Devolução" },
    { name: "Garantia" },
    { name: "Mau Uso" },
    { name: "Não Encontrado" },
    { name: "Próxima Triagem" },
]);

const productTableData = computed(() => {
    const productsGrouped = props.materialsData.productsGrouped || [];
    if (!productsGrouped.length) return [];

    return productsGrouped.map(item => ({
        family: item.family,
        product: item.product,
        total: item.total,
        recovered: item.status_counts?.recuperado || 0,
        return: item.status_counts?.devolucao || 0,
        warranty: item.warranty,
        misuse: item.status_counts?.['mau uso'] || 0,
        not_found: item.status_counts?.['nao encontrado'] || 0,
        next_screening: item.status_counts?.['proxima triagem'] || 0,
    }));
});

const tableId = ref("customerTable");
const productTableId = ref("productTable");

</script>

<template>

    <div class="mt-8">
        <h3 class="text-center font-medium text-gray-800 dark:text-neutral-200">Cliente</h3>
        <Datatable :thead="tableHeaders" :tbody="tableData" :id="tableId" />
    </div>

    <div class="mt-8">
        <h3 class="text-center font-medium text-gray-800 dark:text-neutral-200">Lista de Produtos</h3>
        <Datatable :thead="productTableHeaders" :tbody="productTableData" :id="productTableId" />
    </div>
</template>