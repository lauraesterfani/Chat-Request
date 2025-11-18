'use client' 

import { useState, useEffect } from 'react';
// Importa o hook de autenticação com o caminho relativo CORRIGIDO
import { useAuth } from '../../context/AuthContext'; 

// =================================================================================
// MOCK DO ROUTER (Usamos window.location.href para simular o useRouter)
// =================================================================================
const useRouter = () => ({
    push: (path: string) => {
        // Redirecionamento nativo
        window.location.href = path; 
    },
});

// Interface para a matrícula (mantida)
interface Enrollment {
  id: string;
  matricula: string;
  curso: string;
}

export default function ChatPage() {
  const router = useRouter(); 
  
  // 2. Consome o estado e funções do Contexto (Incluindo 'token' e 'isAuthenticated')
  const { user, isLoading, logout, isAuthenticated, token } = useAuth();
  
  // --- ESTADOS DO CHAT (Mantidos) ---
  const [etapa, setEtapa] = useState(0); 
  const [matriculas, setMatriculas] = useState<Enrollment[]>([]);
  const [matriculaSelecionada, setMatriculaSelecionada] = useState<Enrollment | null>(null);

  // =================================================================================
  // 3. LÓGICA DE CARREGAMENTO DE DADOS E REDIRECIONAMENTO
  // =================================================================================
  
  useEffect(() => {
    // A) Verifica a autenticação DEPOIS que o contexto carregou (isLoading = false)
    if (!isLoading && !isAuthenticated) {
        // Se não estiver autenticado, redireciona para o login
        router.push('/login'); 
        return;
    }

    // B) Se estiver autenticado, carrega dados específicos da página (ex: matrículas)
    if (!isLoading && isAuthenticated && user) {
        // AQUI você faria um FETCH para o seu backend para buscar as matrículas
        // Exemplo: fetch('/api/enrollments', { headers: { 'Authorization': `Bearer ${token}` }})
        
        // Mock de carregamento das matrículas
        const mockMatriculas: Enrollment[] = [
            { id: 'uuid-qualquer-123', matricula: '20250007', curso: 'Sistemas Para Internet' },
            { id: 'uuid-outra-456', matricula: '20240001', curso: 'Gestão da Qualidade' }
        ];
        setMatriculas(mockMatriculas);
    }
  }, [isLoading, isAuthenticated, user, token, router]);


  // --- Lógica de Ações (Mantida) ---
  const handleSelecionarMatricula = (matricula: Enrollment) => {
    setMatriculaSelecionada(matricula);
    setEtapa(1); 
  };

  const handleConsultar = () => {
    router.push('/requests'); 
  };
  
  const handleSolicitar = () => {
    router.push('/requests/new');
  };
  
  const handleLogout = () => {
    logout(); // 4. Chama o logout do Contexto (o Contexto já cuida de limpar o storage e redirecionar)
  }

  // =================================================================================
  // 5. RENDERIZAÇÃO CONDICIONAL
  // =================================================================================

  // Mostra um estado de carregamento enquanto verifica a autenticação
  if (isLoading || !isAuthenticated) { // Usando isAuthenticated para verificar se o usuário está logado
    // Enquanto isLoading for true, ou se o usuário não estiver autenticado.
    return (
      <div className="flex flex-col items-center justify-center min-h-screen bg-gray-50">
          <svg className="animate-spin h-8 w-8 text-[#1a472a]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p className="text-xl text-gray-500 mt-4">Verificando sessão...</p>
      </div>
    );
  }

  // --- RENDERIZAÇÃO DO DASHBOARD (Quando autenticado) ---
  return (
    <div className="max-w-3xl mx-auto p-4 sm:p-6 md:p-8">
      
      {/* Header com Logout e nome do usuário REAL (Usando user.name do contexto) */}
      <header className="flex justify-between items-center mb-10">
        <h1 className="text-center text-4xl font-light text-gray-700">
          Olá, <span className="font-semibold text-[#1a472a]">{user!.name.split(' ')[0]}</span>!
        </h1>
        <button
            onClick={handleLogout}
            className="py-1 px-3 text-sm bg-red-600 text-white font-medium rounded-full shadow hover:bg-red-700 transition"
        >
            Sair
        </button>
      </header>


      {/* Botões de Ação Principais */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-10">
        <button
          onClick={handleSolicitar}
          className="p-8 bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow text-center text-xl font-medium text-[#1a472a] border border-gray-200"
        >
          Solicitar Requerimento
        </button>
        <button
          onClick={handleConsultar}
          className="p-8 bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow text-center text-xl font-medium text-[#1a472a] border border-gray-200"
        >
          Consultar Requerimento
        </button>
      </div>

      {/* --- FLUXO DO CHAT (Mantido) --- */}
      <div className="mt-12 space-y-4">
        
        {/* Bloco 1: Início (Botão "Solicitar Requerimento") */}
        <div className="flex justify-end">
          <div className="bg-[#1a472a] text-white p-4 rounded-lg rounded-br-none shadow-md max-w-xs">
            Solicitar Requerimento
          </div>
        </div>
        
        {/* Bloco 2: Pergunta da Matrícula */}
        <div className="flex justify-start">
          <div className="bg-white text-gray-800 p-4 rounded-lg rounded-bl-none shadow-md max-w-xs border border-gray-200">
            Ok! Primeiro, selecione sua matrícula.
          </div>
        </div>

        {/* Bloco 3: Opções de Matrícula */}
        <div className="space-y-3 pt-4">
          {matriculas.map((mat) => (
            <button
              key={mat.id}
              onClick={() => handleSelecionarMatricula(mat)}
              className="w-full text-left p-4 bg-green-50 border border-green-200 text-green-900 rounded-xl shadow-sm hover:bg-green-100 hover:border-green-300 transition-colors focus:ring-4 focus:ring-green-400 focus:outline-none"
            >
              <span className="font-bold">{mat.matricula}</span> - {mat.curso}
            </button>
          ))}
        </div>
        
        {/* Bloco 4: Resposta do Usuário (Aparece depois de selecionar) */}
        {etapa >= 1 && matriculaSelecionada && (
          <div className="flex justify-end">
            <div className="bg-[#1a472a] text-white p-4 rounded-lg rounded-br-none shadow-md max-w-xs">
              {matriculaSelecionada.matricula} - {matriculaSelecionada.curso}
            </div>
          </div>
        )}

        {/* Bloco 5: Próxima Pergunta do Bot */}
        {etapa >= 1 && (
           <div className="flex justify-start">
            <div className="bg-white text-gray-800 p-4 rounded-lg rounded-bl-none shadow-md max-w-xs border border-gray-200">
              Perfeito. Agora, qual o tipo de requerimento?
            </div>
          </div>
        )}
      </div>
      
    </div>
  );
}