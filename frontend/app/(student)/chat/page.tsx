'use client'

import { useState, useEffect } from 'react';
import { useAuth } from '@/app/context/AuthContext';
import { useRouter } from 'next/navigation';

interface Enrollment {
  id: string;
  matricula: string;
  curso: string;
}

export default function ChatPage() {
  const { user, logout } = useAuth();
  const router = useRouter();

  const [etapa, setEtapa] = useState(0);
  const [matriculas, setMatriculas] = useState<Enrollment[]>([]);
  const [matriculaSelecionada, setMatriculaSelecionada] = useState<Enrollment | null>(null);
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  // ✔ Mensagens enviadas pelo usuário (ou futuramente pelo bot)
  const [chatMessages, setChatMessages] = useState<
    { author: 'user' | 'bot', text: string }[]
  >([]);

  // ✔ Caixa de texto
  const [mensagem, setMensagem] = useState("");

  useEffect(() => {
    const mockMatriculas: Enrollment[] = [
      {
        id: 'uuid-123',
        matricula: '20250007',
        curso: 'Sistemas Para Internet',
      },
    ];
    setMatriculas(mockMatriculas);
  }, []);

  const handleSelecionarMatricula = (matricula: Enrollment) => {
    setMatriculaSelecionada(matricula);
    setEtapa(1);
  };

  const handleConsultar = () => router.push('/requests');
  const handleSolicitar = () => router.push('/requests/new');

  const handleMenuClick = (path: string) => {
    setIsMenuOpen(false);
    if (path === 'logout') {
      logout();
      router.push('/login');
    } else router.push(path);
  };

  // ✔ Agora o botão realmente envia para o chat
  const handleEnviar = () => {
    if (mensagem.trim().length === 0) return;

    setChatMessages((prev) => [
      ...prev,
      { author: 'user', text: mensagem }
    ]);

    setMensagem("");
  };

  return (
    <div className="max-w-3xl mx-auto py-10 relative pb-28">

      {/* CABEÇALHO */}
      <div className="flex justify-between items-center mb-8 px-4 sm:px-0">
        <h1 className="text-3xl font-light text-gray-700">
          Olá, {user?.name.split(' ')[0]}!
        </h1>

        <div className="relative">
          <button
            onClick={() => setIsMenuOpen(!isMenuOpen)}
            className="p-2 text-gray-600 hover:text-gray-900 focus:ring-2 focus:ring-green-500 rounded-full"
          >
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
          </button>

          {isMenuOpen && (
            <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-100 z-10">
              <button
                onClick={() => handleMenuClick('/me')}
                className="w-full px-4 py-2 text-sm text-gray-700 hover:bg-green-50 text-left"
              >
                Dados da Conta
              </button>
              <button
                onClick={() => handleMenuClick('logout')}
                className="w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 border-t border-gray-100 text-left"
              >
                Sair
              </button>
            </div>
          )}
        </div>
      </div>

      {/* AÇÕES */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-10">
        <button
          onClick={handleSolicitar}
          className="p-8 bg-white rounded-lg shadow-md hover:shadow-lg text-xl text-gray-700"
        >
          Solicitar Requerimento
        </button>

        <button
          onClick={handleConsultar}
          className="p-8 bg-white rounded-lg shadow-md hover:shadow-lg text-xl text-gray-700"
        >
          Consultar Requerimento
        </button>
      </div>

      {/* CHAT */}
      <div className="mt-12 space-y-4">

        {/* Fluxo inicial fixo */}
        <div className="flex justify-end">
          <div className="bg-green-600 text-white p-4 rounded-lg rounded-br-none shadow-md">
            Solicitar Requerimento
          </div>
        </div>

        <div className="flex justify-start">
          <div className="bg-white text-gray-800 p-4 rounded-lg rounded-bl-none shadow-md">
            Ok! Primeiro, selecione sua matrícula.
          </div>
        </div>

        {/* Botões de matrícula */}
        <div className="space-y-3 pt-4">
          {matriculas.map((mat) => (
            <button
              key={mat.id}
              onClick={() => handleSelecionarMatricula(mat)}
              className="w-full px-4 py-3 bg-green-50 border border-green-200 text-green-900 rounded-lg hover:bg-green-100 shadow-sm"
            >
              <span className="font-bold">{mat.matricula}</span> – {mat.curso}
            </button>
          ))}
        </div>

        {/* Resposta do usuário */}
        {etapa >= 1 && matriculaSelecionada && (
          <div className="flex justify-end">
            <div className="bg-green-600 text-white p-4 rounded-lg rounded-br-none shadow-md">
              {matriculaSelecionada.matricula} - {matriculaSelecionada.curso}
            </div>
          </div>
        )}

        {/* Bot continua */}
        {etapa >= 1 && (
          <div className="flex justify-start">
            <div className="bg-white text-gray-800 p-4 rounded-lg rounded-bl-none shadow-md">
              Perfeito. Agora, qual o tipo de requerimento?
            </div>
          </div>
        )}

        {/* ----------------------------- */}
        {/* MENSAGENS DO CHAT DINÂMICAS   */}
        {/* ----------------------------- */}
        {chatMessages.map((msg, index) => (
          <div
            key={index}
            className={`flex ${msg.author === 'user' ? 'justify-end' : 'justify-start'}`}
          >
            <div
              className={`
                p-4 rounded-lg shadow-md max-w-xs 
                ${msg.author === 'user'
                  ? 'bg-green-600 text-white rounded-br-none'
                  : 'bg-white text-gray-800 rounded-bl-none'
                }
              `}
            >
              {msg.text}
            </div>
          </div>
        ))}

      </div>

      {/* CAIXA DE TEXTO */}
      <div className="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 py-3 px-4">
        <div className="max-w-3xl mx-auto flex gap-3 items-center">

          <input
            value={mensagem}
            onChange={(e) => setMensagem(e.target.value)}
            placeholder="Digite uma mensagem..."
            className="flex-1 p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 outline-none"
          />

          <button
            onClick={handleEnviar}
            disabled={mensagem.trim().length === 0}
            className={`p-3 rounded-full transition 
              ${mensagem.trim().length === 0
                ? "text-gray-400 cursor-not-allowed"
                : "text-green-600 hover:text-green-700"
              }`}
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
              viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor"
              className="w-7 h-7">
              <path strokeLinecap="round" strokeLinejoin="round"
                d="M3.4 20.6 21 12 3.4 3.4 3 10l11 2-11 2.1z" />
            </svg>
          </button>

        </div>
      </div>
    </div>
  );
}
