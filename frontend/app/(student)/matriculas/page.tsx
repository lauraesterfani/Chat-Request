'use client' // Página que vai buscar dados (fetch)

import { useState, useEffect } from 'react';
import { useAuth } from '@/context/AuthContext';

// 1. Interface para os dados da matrícula
// (Combinei o teu protótipo com o teu Model do Laravel)
interface Matricula {
  id: string;
  curso: string;
  campus: string; // (Não existe no backend, vamos mockar)
  status: 'active' | 'locked' | 'finished'; //
  numero: string; // (enrollment.enrollment)
  ingresso: string; // (Não existe no backend, vamos mockar)
  turno: string; // (Não existe no backend, vamos mockar)
}
// Componente para o "Tag" de Status (CORRIGIDO)
const StatusTag = ({ status }: { status: Matricula['status'] }) => {
  
  // 1. A interface do objeto agora tem 'text', 'bg', e 'textColor'
  const statusMap: Record<Matricula['status'], { text: string; bg: string; textColor: string }> = {
    active:   { text: 'Matriculado', bg: 'bg-green-100',   textColor: 'text-green-800' },
    locked:   { text: 'Trancado',    bg: 'bg-yellow-100',  textColor: 'text-yellow-800' },
    finished: { text: 'Concluído',   bg: 'bg-blue-100',    textColor: 'text-blue-800' },
  };

  // 2. A lógica de fallback continua a mesma
  const s = statusMap[status] || statusMap.locked; 

  return (
    // 3. O className agora usa s.bg e s.textColor
    <span className={`px-3 py-1 text-sm font-medium rounded-full ${s.bg} ${s.textColor}`}>
      {s.text}
    </span>
  );
};

export default function MatriculasPage() {
  const { user } = useAuth();
  const [matriculas, setMatriculas] = useState<Matricula[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    // 2. Lógica para buscar os dados
    const fetchMatriculas = async () => {
      // --- IMPORTANTE ---
      // Quando o backend estiver pronto, aqui deves fazer:
      // const response = await fetch('/api/minhas-matriculas', { headers: { 'Authorization': `Bearer ${token}` } });
      // const data = await response.json();
      // setMatriculas(data);
      // --- FIM IMPORTANTE ---

      // Por agora, usamos dados MOCKADOS:
      const mockData: Matricula[] = [
        {
          id: 'uuid-123-abc',
          curso: 'Sistemas Para Internet', //
          campus: 'Campus Igarassu - Presencial',
          status: 'active', //
          numero: '20250007', //
          ingresso: '2024.1', //
          turno: 'Manhã', //
        },
        // Podes adicionar outra matrícula de exemplo aqui
        {
          id: 'uuid-456-xyz',
          curso: 'Técnico em Logística', //
          campus: 'Campus Igarassu - EAD',
          status: 'locked',
          numero: '20230012',
          ingresso: '2023.1',
          turno: 'Noite',
        },
      ];
      
      setMatriculas(mockData);
      setIsLoading(false);
    };

    fetchMatriculas();
  }, [user]); // Depende do 'user' para saber quem está logado

  if (isLoading) {
    return <div>A carregar matrículas...</div>;
  }

  return (
    <div className="max-w-5xl mx-auto">
      {/* O título "Minhas Matrículas" virá do layout.tsx */}
      
      <div className="space-y-6">
        {/* 3. Loop sobre as matrículas encontradas */}
        {matriculas.map((mat) => (
          <div key={mat.id} className="bg-white p-6 rounded-lg shadow-md">
            
            {/* Linha Superior: Nome do Curso e Status */}
            <div className="flex justify-between items-start mb-4">
              <div>
                <h2 className="text-2xl font-bold text-blue-800">{mat.curso}</h2>
                <p className="text-gray-600">{mat.campus}</p>
              </div>
              <StatusTag status={mat.status} />
            </div>

            {/* Linha Inferior: Detalhes */}
            <div className="grid grid-cols-3 gap-4 pt-4 border-t border-gray-200">
              <div>
                <p className="text-sm text-gray-500">N° Matrícula</p>
                <p className="text-lg font-medium text-gray-800">{mat.numero}</p>
              </div>
              <div>
                <p className="text-sm text-gray-500">Ingresso</p>
                <p className="text-lg font-medium text-gray-800">{mat.ingresso}</p>
              </div>
              <div>
                <p className="text-sm text-gray-500">Turno</p>
                <p className="text-lg font-medium text-gray-800">{mat.turno}</p>
              </div>
            </div>
            
          </div>
        ))}
        
        {matriculas.length === 0 && (
          <div className="bg-white p-6 rounded-lg shadow-md text-center">
            <p className="text-gray-600">Nenhuma matrícula encontrada.</p>
          </div>
        )}
      </div>
    </div>
  );
}