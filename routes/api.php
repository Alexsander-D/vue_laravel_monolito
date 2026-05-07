<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/**
 * 🔹 Retorna todos os estados do IBGE
 */
Route::get('/estados', function () {
    $response = Http::get('https://servicodados.ibge.gov.br/api/v1/localidades/estados?orderBy=nome');
    return response()->json($response->json(), $response->status());
});

/**
 * 🔹 Retorna todos os municípios de um estado (por sigla)
 */
Route::get('/estados/{sigla}/municipios', function ($sigla) {
    $response = Http::get("https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$sigla}/municipios?orderBy=nome");
    return response()->json($response->json(), $response->status());
});

/**
 * 🔹 Consulta CNPJ (ReceitaWS)
 */
Route::get('/cnpj/{cnpj}', function ($cnpj) {
    $response = Http::get("https://www.receitaws.com.br/v1/cnpj/{$cnpj}");
    return response()->json($response->json(), $response->status());
});

/**
 * 🔹 Consulta CEP (ViaCEP)
 */
Route::get('/cep/{cep}', function ($cep) {
    $response = Http::get("https://viacep.com.br/ws/{$cep}/json/");
    return response()->json($response->json(), $response->status());
});

/**
 * 🔹 Usuário autenticado (opcional)
 */
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
