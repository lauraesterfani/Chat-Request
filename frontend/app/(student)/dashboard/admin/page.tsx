"use client";

import Link from "next/link";

export default function DashboardPage() {
  return (
    <div className="min-h-screen flex bg-[#E5E5E5]">
      {/* Painel esquerdo */}
      <aside className="w-64 bg-[#0B0D3A] text-white flex flex-col p-6">
        <h1 className="text-2xl font-bold mb-8">Chat Request</h1>
        <p className="text-sm opacity-80">
          Painel de gerenciamento de requerimentos acadêmicos
        </p>
      </aside>

      {/* Conteúdo principal */}
      <main className="flex-1 p-10">
        <h2 className="text-2xl font-semibold mb-6">Painel principal</h2>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-3xl">

          {/* Cadastrar staff */}
          <Link
            href="/signup/staff"
            className="bg-white rounded-2xl shadow p-6 text-left hover:shadow-md transition"
          >
            <h3 className="text-lg font-medium">Cadastrar staff</h3>
            <p className="text-sm text-gray-600">
              Adicionar novos membros da equipe
            </p>
          </Link>

          {/* Visualizar staffs */}
          <button className="bg-white rounded-2xl shadow p-6 text-left hover:shadow-md transition">
            <h3 className="text-lg font-medium">Visualizar staffs</h3>
            <p className="text-sm text-gray-600">
              Ver staffs já cadastrados
            </p>
          </button>

          {/* Visualizar requerimentos */}
       <Link
  href="/request"
  className="bg-white rounded-2xl shadow p-6 text-left hover:shadow-md transition"
>
  <h3 className="text-lg font-medium">Visualizar requerimentos</h3>
  <p className="text-sm text-gray-600">
    Acompanhar solicitações dos alunos
  </p>
</Link>


          {/* Novo requerimento */}
          <Link
            href="/requests/new"
            className="bg-[#1A73E8] text-white rounded-2xl shadow p-6 hover:opacity-90 transition"
          >
            <h3 className="text-lg font-medium">Novo requerimento</h3>
            <p className="text-sm opacity-90">
              Criar uma nova solicitação
            </p>
          </Link>

        </div>
      </main>
    </div>
  );
}
