'use client' // Esta página é interativa (botões, estado do chat)

import { useState, useEffect } from 'react';
import { useAuth } from '@/app/context/AuthContext';
import { useRouter } from 'next/navigation';

// Interface para a matrícula (vamos usar dados mockados por agora)
interface Enrollment {
  id: string;
  matricula: string;
  curso: string;
}

export default function ChatPage() {
  const { user } = useAuth(); // Pega o utilizador logado
  const router = useRouter(); // Para navegar

  // Estados para o fluxo do chat
  const [etapa, setEtapa] = useState(0); // 0 = Início, 1 = Selecionou matrícula
  const [matriculas, setMatriculas] = useState<Enrollment[]>([]);
  const [matriculaSelecionada, setMatriculaSelecionada] = useState<Enrollment | null>(null);

  // --- Lógica de Dados ---
  useEffect(() => {
    // TODO: Precisamos de uma rota no backend (ex: GET /api/my-enrollments)
    // que devolva as matrículas do 'user->id' logado.
    // Por agora, vamos usar dados MOCKADOS baseados no teu protótipo.
    
    // O teu backend tem matrículas (Enrollment) e cursos (FIXED_COURSES)
    //
    const mockMatriculas: Enrollment[] = [
      {
        id: 'uuid-qualquer-123',
        matricula: '20250007',
        curso: 'Sistemas Para Internet',
      },
      // { id: 'uuid-outra-456', matricula: '20240001', curso: 'Gestão da Qualidade' }
    ];
    setMatriculas(mockMatriculas);
  }, []);

  // --- Lógica de Ações ---
  const handleSelecionarMatricula = (matricula: Enrollment) => {
    setMatriculaSelecionada(matricula);
    setEtapa(1); // Avança para a próxima etapa
  };

  const handleConsultar = () => {
    // Navega para a página de lista de requerimentos do aluno
    router.push('/requests'); 
  };
  
  const handleSolicitar = () => {
    // Navega para a página de novo requerimento
    router.push('/requests/new');
  };

  return (
    <div className="max-w-3xl mx-auto">
      {/* Título "Olá, Usuário!" */}
      <h1 className="text-center text-4xl font-light text-gray-700">
        Olá, {user?.name.split(' ')[0]}!
      </h1>

      {/* Botões de Ação Principais */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-10">
        <button
          onClick={handleSolicitar}
          className="p-8 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow text-center text-xl text-gray-700"
        >
          Solicitar Requerimento
        </button>
        <button
          onClick={handleConsultar}
          className="p-8 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow text-center text-xl text-gray-700"
        >
          Consultar Requerimento
        </button>
      </div>

      {/* --- FLUXO DO CHAT --- */}
      <div className="mt-12 space-y-4">
        
        {/* Bloco 1: Início (Botão "Solicitar Requerimento") */}
        <div className="flex justify-end">
          <div className="bg-green-600 text-white p-4 rounded-lg rounded-br-none shadow-md">
            Solicitar Requerimento
          </div>
        </div>
        
        {/* Bloco 2: Pergunta da Matrícula */}
        <div className="flex justify-start">
          <div className="bg-white text-gray-800 p-4 rounded-lg rounded-bl-none shadow-md">
            Ok! Primeiro, selecione sua matrícula.
          </div>
        </div>

        {/* Bloco 3: Opções de Matrícula */}
        <div className="space-y-3 pt-4">
          {matriculas.map((mat) => (
            <button
              key={mat.id}
              onClick={() => handleSelecionarMatricula(mat)}
              className="w-full text-left p-4 bg-green-50 border border-green-200 text-green-900 rounded-lg shadow-sm hover:bg-green-100 hover:border-green-300 transition-colors"
            >
              <span className="font-bold">{mat.matricula}</span> - {mat.curso}
            </button>
          ))}
        </div>
        
        {/* Bloco 4: (Aparece depois de selecionar) */}
        {etapa >= 1 && matriculaSelecionada && (
          <div className="flex justify-end">
            <div className="bg-green-600 text-white p-4 rounded-lg rounded-br-none shadow-md">
              {matriculaSelecionada.matricula} - {matriculaSelecionada.curso}
            </div>
          </div>
        )}

        {/* TODO: Continuar o fluxo do chat... */}
        {etapa >= 1 && (
           <div className="flex justify-start">
            <div className="bg-white text-gray-800 p-4 rounded-lg rounded-bl-none shadow-md">
              Perfeito. Agora, qual o tipo de requerimento?
              {/* (Aqui viria um <select> com os tipos de requerimento da API) */}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}