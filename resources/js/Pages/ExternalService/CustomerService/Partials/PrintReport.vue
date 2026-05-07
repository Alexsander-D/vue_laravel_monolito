<script setup>
import { onMounted } from "vue";

defineProps({
    screeningId: Number,
    rows: Array,
    groupedProducts: Array,
    customer: Object,
    technicalScales: Array,
});

onMounted(() => {
    setTimeout(() => window.print(), 500);
});
</script>

<template>
    <div class="p-8 print:p-0 font-sans text-sm">

        <!-- Título -->
        <h1 class="text-xl font-bold mb-6 text-center">
            LAUDO TRIAGEM # {{ screeningId }}
        </h1>

        <!-- Dados Cliente -->
        <div class="border p-4 rounded mb-8">
            <h2 class="font-bold text-lg mb-3">Dados do Cliente</h2>

            <div class="grid grid-cols-2 gap-3 text-sm">
                <div><strong>Identificação:</strong> {{ customer.type_person }}</div>
                <div><strong>Razão Social:</strong> {{ customer.company_name }}</div>
                <div><strong>Fantasia:</strong> {{ customer.trade_name }}</div>
                <div><strong>Data Inicial:</strong> {{ customer.service_start }}</div>
                <div><strong>Data Final:</strong> {{ customer.completion_date }}</div>
                <div><strong>CEP:</strong> {{ customer.cep }}</div>
                <div><strong>Estado:</strong> {{ customer.state }}</div>
                <div><strong>Cidade:</strong> {{ customer.city }}</div>
                <div><strong>Rua:</strong> {{ customer.road }}</div>
                <div><strong>Bairro:</strong> {{ customer.district }}</div>
                <div><strong>Número:</strong> {{ customer.number }}</div>
                <div><strong>Telefone:</strong> {{ customer.telephone }}</div>
                <div><strong>Email:</strong> {{ customer.email }}</div>
                <div>
                    <strong>Técnicos Escalados:</strong>
                    {{
                        technicalScales && technicalScales.length > 0
                            ? technicalScales.map(t => t.user?.name).join(", ")
                            : "Nenhum técnico escalado"
                    }}

                </div>
            </div>
        </div>

        <!-- Tabela Detalhada -->
        <h2 class="font-bold text-lg mb-2">Rastreabilidade</h2>
        <table class="w-full border-collapse mb-10 text-xs">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border p-1">Produto</th>
                    <th class="border p-1">Família</th>
                    <th class="border p-1">Valor</th>
                    <th class="border p-1">Componente</th>
                    <th class="border p-1">Defeito</th>
                    <th class="border p-1">Solução</th>
                    <th class="border p-1">IMEI1</th>
                    <th class="border p-1">IMEI2</th>
                    <th class="border p-1">S/N</th>
                    <th class="border p-1">Status</th>
                    <th class="border p-1">Obs</th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="(r, i) in rows" :key="i">
                    <td class="border p-1">{{ r.product }}</td>
                    <td class="border p-1">{{ r.family }}</td>
                    <td class="border p-1">{{ r.price }}</td>
                    <td class="border p-1">{{ r.component }}</td>
                    <td class="border p-1">{{ r.defect }}</td>
                    <td class="border p-1">{{ r.solution }}</td>
                    <td class="border p-1">{{ r.imei1 }}</td>
                    <td class="border p-1">{{ r.imei2 }}</td>
                    <td class="border p-1">{{ r.serial_number }}</td>
                    <td class="border p-1">{{ r.status }}</td>
                    <td class="border p-1">{{ r.observation }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Tabela Agrupada -->
        <h2 class="font-bold text-lg mb-2">Produtos</h2>
        <table class="w-full border-collapse text-xs">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border p-1">Família</th>
                    <th class="border p-1">Produto</th>
                    <th class="border p-1">Garantia</th>
                    <th class="border p-1">Total</th>
                    <th class="border p-1">Recup.</th>
                    <th class="border p-1">Devol.</th>
                    <th class="border p-1">Mau Uso</th>
                    <th class="border p-1">Não Encontrado</th>
                    <th class="border p-1">Próx. Triagem</th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="(p, i) in groupedProducts" :key="i">
                    <td class="border p-1">{{ p.family }}</td>
                    <td class="border p-1">{{ p.product }}</td>
                    <td class="border p-1">{{ p.warranty }}</td>
                    <td class="border p-1">{{ p.total }}</td>
                    <td class="border p-1">{{ p.recovered }}</td>
                    <td class="border p-1">{{ p.return }}</td>
                    <td class="border p-1">{{ p.misuse }}</td>
                    <td class="border p-1">{{ p.not_found }}</td>
                    <td class="border p-1">{{ p.next_screening }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<style>
@media print {
    body {
        margin: 0;
        padding: 0;
    }

    table {
        page-break-inside: avoid;
    }
}
</style>
