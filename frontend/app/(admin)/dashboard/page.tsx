'use client' // Esta página terá filtros (useState), por isso é Client

import { useState } from 'react';

// Dados de exemplo (MOCK) - Vamos substituir isto por dados reais da API
const mockRequerimentos = [
  {
    protocolo: '20250814-1147',
    status: 'Em Análise',
    aluno: 'Luiz Inácio',
    tipo: 'Histórico Escolar',
    data: '12/11/2025',
  },
  {
    protocolo: '20250814-1147',
    status: 'Deferido',
    aluno: 'Catarina Silva',
    tipo: 'Transferência de Turno',
    data: '12/11/2025',
  },
  {
    protocolo: '20250814-1147',
    status: 'Indeferido',
    aluno: 'Dylan Borges',
    tipo: 'Ementa de Disciplina',
    data: '12/11/2025',
  },
];

// Componente para os "Tags" de Status
const StatusTag = ({ status }: { status: string }) => {
  let bgColor = '';
  let textColor = '';

  switch (status) {
    case 'Em Análise':
      bgColor = 'bg-yellow-100';
      textColor = 'text-yellow-800';
      break;
    case 'Deferido':
      bgColor = 'bg-green-100';
      textColor = 'text-green-800';
      break;
    case 'Indeferido':
      bgColor = 'bg-red-100';
      textColor = 'text-red-800';
      break;
    default:
      bgColor = 'bg-gray-100';
      textColor = 'text-gray-800';
  }

  return (
    <span className={`px-3 py-1 text-sm font-medium rounded-full ${bgColor} ${textColor}`}>
      {status}
    </span>
  );
};


export default function DashboardPage() {
  // Estado para controlar o filtro ativo
  const [filtro, setFiltro] = useState('Todos');

  // TODO: Fazer o fetch dos dados da API (ex: /api/dashboard/requerimentos)
  // Por enquanto, usamos os dados mockados
  const requerimentos = mockRequerimentos;

  return (
    <div>
      <h1 className="text-4xl font-bold text-gray-800">Lista de Requerimentos</h1>

      {/* Botões de Filtro */}
      <div className="flex space-x-2 mt-8 mb-6">
        {['Todos', 'Em Análise', 'Deferido', 'Indeferido'].map((tab) => (
          <button
            key={tab}
            onClick={() => setFiltro(tab)}
            className={`px-5 py-2 rounded-lg font-medium transition-colors
              ${filtro === tab 
                ? 'bg-[#1a472a] text-white' 
                : 'bg-white text-gray-600 hover:bg-gray-50'
              }`}
          >
            {tab}
          </button>
        ))}
      </div>

      {/* Tabela de Requerimentos */}
      <div className="bg-white rounded-lg shadow-md overflow-hidden">
        <table className="w-full min-w-[600px]">
          {/* Cabeçalho da Tabela */}
          <thead className="bg-[#1a472a] text-white">
            <tr>
              <th className="py-3 px-5 text-left text-sm font-semibold uppercase">Protocolo</th>
              <th className="py-3 px-5 text-left text-sm font-semibold uppercase">Status</th>
              <th className="py-3 px-5 text-left text-sm font-semibold uppercase">Aluno</th>
              <th className="py-3 px-5 text-left text-sm font-semibold uppercase">Tipo de Requerimento</th>
              <th className="py-3 px-5 text-left text-sm font-semibold uppercase">Data de Criação</th>
            </tr>
          </thead>
          
          {/* Corpo da Tabela */}
          <tbody className="text-gray-700">
            {requerimentos.map((req, index) => (
              <tr key={index} className="border-b border-gray-200 hover:bg-gray-50">
                <td className="py-4 px-5">{req.protocolo}</td>
                <td className="py-4 px-5">
                  <StatusTag status={req.status} />
                </td>
                <td className="py-4 px-5">{req.aluno}</td>
                <td className="py-4 px-5">{req.tipo}</td>
                <td className="py-4 px-5">{req.data}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}