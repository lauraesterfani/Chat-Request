'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '../../../context/AuthContext'; 
import Link from 'next/link';

interface TypeRequest {
  id: string;
  name: string;
  description?: string;
}

export default function NewRequestPage() {
  const { token } = useAuth();
  const router = useRouter();

  // Estados do Formulário
  const [types, setTypes] = useState<TypeRequest[]>([]);
  const [selectedType, setSelectedType] = useState('');
  const [subject, setSubject] = useState(''); 
  const [description, setDescription] = useState('');
  const [file, setFile] = useState<File | null>(null);
  
  // Estados de Controle (Loading/Erro)
  const [loading, setLoading] = useState(false);
  const [loadingTypes, setLoadingTypes] = useState(true);
  const [error, setError] = useState('');

  // 1. Buscar Tipos de Requerimento + Verificar Escolha do Chat
  useEffect(() => {
    const fetchTypes = async () => {
        if (!token) return;
        try {
            const response = await fetch('http://localhost:8000/api/type-requests', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) throw new Error("Erro ao carregar tipos.");
            
            const data = await response.json();
            const typesData = data.data || data;
            setTypes(typesData);

            // INTEGRAÇÃO COM O CHAT: Verifica se já veio com uma escolha
            const savedType = localStorage.getItem('selected_type_id');
            if (savedType) {
                // Verifica se o ID salvo existe na lista de tipos retornada
                const typeExists = typesData.find((t: TypeRequest) => t.id === savedType);
                if (typeExists) {
                    setSelectedType(savedType);
                }
                localStorage.removeItem('selected_type_id'); // Limpa para não forçar sempre
            }

        } catch (err) {
            console.error(err);
            setError("Não foi possível carregar os tipos de requerimento.");
        } finally {
            setLoadingTypes(false);
        }
    };
    fetchTypes();
  }, [token]);

  // 2. Enviar Pedido (Submit)
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
        let documentIds: string[] = [];

        // A) Upload do Documento (Se o usuário selecionou um arquivo)
        if (file) {
            const formData = new FormData();
            formData.append('file', file); 

            const uploadRes = await fetch('http://localhost:8000/api/documents/upload', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                },
                body: formData
            });

            if (!uploadRes.ok) throw new Error("Falha no upload do documento.");
            
            const uploadData = await uploadRes.json();
            
            // Tenta pegar o ID de várias formas possíveis (dependendo da resposta da API)
            if (uploadData.id) {
                documentIds.push(uploadData.id);
            } else if (uploadData.data && uploadData.data.id) {
                documentIds.push(uploadData.data.id);
            }
        }

        // B) Criar Requerimento
        const payload = {
            type_id: selectedType, 
            subject: subject,      
            description: description,
            document_ids: documentIds 
        };

        const res = await fetch('http://localhost:8000/api/requests', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (!res.ok) {
            const msg = data.message || data.msg || "Erro ao criar requerimento.";
            throw new Error(msg);
        }

        // Sucesso! Volta para o Dashboard
        router.push('/me');

    } catch (err: any) {
        console.error(err);
        setError(err.message || "Ocorreu um erro inesperado.");
    } finally {
        setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 pb-20">
       
       {/* Cabeçalho com Botão Voltar */}
       <div className="bg-white border-b border-gray-200 px-6 py-4 flex items-center gap-4 sticky top-0 z-10 shadow-sm">
          <Link href="/me" className="p-2 hover:bg-gray-100 rounded-full transition text-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m15 18-6-6 6-6"/></svg>
          </Link>
          <h1 className="text-xl font-bold text-slate-800">Detalhes do Pedido</h1>
       </div>

       <div className="max-w-2xl mx-auto px-6 mt-8 animate-fadeIn">
          
          {error && (
            <div className="mb-6 p-4 bg-red-50 text-red-700 rounded-xl border border-red-100 text-sm flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                {error}
            </div>
          )}

          <form onSubmit={handleSubmit} className="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 space-y-6">
              
              {/* Tipo de Solicitação */}
              <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Tipo de Solicitação</label>
                  <select 
                    value={selectedType}
                    onChange={(e) => setSelectedType(e.target.value)}
                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-[#15803d] focus:ring-2 focus:ring-green-100 outline-none transition-all text-slate-800 disabled:opacity-50"
                    required
                    disabled={loadingTypes}
                  >
                      <option value="" disabled>Selecione uma opção...</option>
                      {types.map(type => (
                          <option key={type.id} value={type.id}>
                              {type.name}
                          </option>
                      ))}
                  </select>
                  {loadingTypes && <p className="text-xs text-gray-400 mt-1 ml-1">Carregando tipos...</p>}
              </div>

              {/* Assunto */}
              <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Assunto Resumido</label>
                  <input 
                    type="text"
                    value={subject}
                    onChange={(e) => setSubject(e.target.value)}
                    placeholder="Ex: Justificativa de falta dia 10"
                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-[#15803d] focus:ring-2 focus:ring-green-100 outline-none transition-all text-slate-800"
                    required
                  />
              </div>

              {/* Descrição */}
              <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Descrição Detalhada</label>
                  <textarea 
                    value={description}
                    onChange={(e) => setDescription(e.target.value)}
                    rows={5}
                    placeholder="Explique sua solicitação com detalhes para agilizar o atendimento..."
                    className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-[#15803d] focus:ring-2 focus:ring-green-100 outline-none transition-all text-slate-800 resize-none"
                    required
                  />
              </div>

              {/* Upload de Arquivo */}
              <div>
                  <label className="block text-sm font-bold text-slate-700 mb-2">Anexar Documento (Opcional)</label>
                  <div className="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:bg-gray-50 transition cursor-pointer relative group">
                      <input 
                        type="file" 
                        onChange={(e) => setFile(e.target.files ? e.target.files[0] : null)}
                        className="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                      />
                      <div className="flex flex-col items-center">
                          <div className={`w-12 h-12 rounded-full flex items-center justify-center mb-3 transition-colors ${file ? 'bg-green-100 text-[#15803d]' : 'bg-gray-100 text-gray-400 group-hover:bg-green-50 group-hover:text-[#15803d]'}`}>
                             {file ? (
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                             ) : (
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                             )}
                          </div>
                          
                          {file ? (
                              <div>
                                <p className="text-sm font-bold text-[#15803d] break-all">{file.name}</p>
                                <p className="text-xs text-green-600 mt-1">Clique para alterar</p>
                              </div>
                          ) : (
                              <>
                                <p className="text-sm font-medium text-slate-600">Clique para fazer upload</p>
                                <p className="text-xs text-gray-400 mt-1">PDF ou Imagens</p>
                              </>
                          )}
                      </div>
                  </div>
              </div>

              {/* Botão de Envio */}
              <button
                type="submit"
                disabled={loading || !selectedType}
                className={`
                    w-full font-bold py-4 rounded-xl shadow-lg mt-4 flex justify-center items-center gap-2 transition-all
                    ${loading || !selectedType 
                        ? 'bg-gray-300 text-gray-500 cursor-not-allowed' 
                        : 'bg-[#15803d] text-white hover:bg-[#166534] hover:shadow-xl hover:-translate-y-0.5'
                    }
                `}
              >
                 {loading ? (
                    <>
                        <span className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        Enviando...
                    </>
                 ) : (
                    "Confirmar e Enviar Pedido"
                 )}
              </button>

          </form>
       </div>
    </div>
  );
}