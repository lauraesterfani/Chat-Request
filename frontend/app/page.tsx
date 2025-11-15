'use client' // 👈 ESSENCIAL para a página de login

import { useState } from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { BsPersonBadge, BsEye, BsEyeSlash } from 'react-icons/bs';
import { useAuth } from '@/context/AuthContext'; // Importe o hook de autenticação

export default function LoginPage() {
  const [cpf, setCpf] = useState('');
  const [senha, setSenha] = useState('');
  const [mostrarSenha, setMostrarSenha] = useState(false);
  const [erro, setErro] = useState<string | null>(null);
  
  const { login } = useAuth(); // Pegue a função de login do contexto
  const [isLoading, setIsLoading] = useState(false);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setErro(null);
    setIsLoading(true);

    const cpfLimpo = cpf.replace(/\D/g, '');

    if (cpfLimpo.length !== 11) {
      setErro('CPF deve ter 11 dígitos.');
      setIsLoading(false);
      return;
    }

    try {
      const resposta = await fetch('http://localhost:8000/api/login', { //
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          cpf: cpfLimpo,
          password: senha, //
        }),
      });

      const dados = await resposta.json();

      if (!resposta.ok) {
        throw new Error(dados.message || 'Credenciais Inválidas');
      }

      // SUCESSO! Chame a função de login do contexto
      if (dados.user && dados.access_token) { //
        login(dados.access_token, dados.user);
      } else {
        throw new Error('Resposta de login inválida do servidor.');
      }

    } catch (err: any) {
      console.error('Falha no login:', err);
      setErro(err.message || 'Não foi possível conectar ao servidor.');
    } finally {
      setIsLoading(false); // Garante que o botão seja liberado
    }
  };

  return (
    <main className="flex min-h-screen">
      
      {/* ===== COLUNA DA ESQUERDA (VERDE) ===== */}
      <div className="w-1/2 bg-[#1a472a] flex flex-col items-center justify-center p-12 text-white text-center">
        <Image
          src="/mascote.png" // Da pasta 'frontend/public'
          alt="Mascote Chat Request"
          width={200}
          height={200}
          priority
        />
        <h1 className="text-4xl font-bold mt-6">
          Bem Vindo de Volta ao Chat Request!
        </h1>
        <p className="text-lg mt-4 max-w-md">
          Faça login para acessar as ferramentas de gerenciamento e consulta de requerimentos.
        </p>
      </div>

      {/* ===== COLUNA DA DIREITA (FORMULÁRIO) ===== */}
      <div className="w-1/2 bg-gray-100 flex items-center justify-center p-12">
        <div className="bg-white p-10 rounded-lg shadow-2xl w-full max-w-md">
          <h2 className="text-3xl font-bold text-center text-[#1a472a]">
            Entrar na Conta
          </h2>
          <p className="text-center text-gray-600 mt-2 mb-8">
            Para acessar seus requerimentos
          </p>

          <form onSubmit={handleLogin} className="space-y-6">
            {/* --- Campo CPF --- */}
            <div>
              <label htmlFor="cpf" className="block text-sm font-medium text-gray-700">
                CPF
              </label>
              <div className="relative mt-1">
                <input
                  id="cpf" name="cpf" type="text"
                  value={cpf} onChange={(e) => setCpf(e.target.value)}
                  placeholder="000.000.000-00" required
                  className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-700 focus:border-green-700"
                />
                <BsPersonBadge className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={20} />
              </div>
            </div>

            {/* --- Campo Senha --- */}
            <div>
              <label htmlFor="senha" className="block text-sm font-medium text-gray-700">
                Senha
              </label>
              <div className="relative mt-1">
                <input
                  id="senha" name="senha"
                  type={mostrarSenha ? 'text' : 'password'}
                  value={senha} onChange={(e) => setSenha(e.target.value)}
                  placeholder="Sua senha" required
                  className="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-700 focus:border-green-700"
                />
                <button
                  type="button"
                  onClick={() => setMostrarSenha(!mostrarSenha)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                >
                  {mostrarSenha ? <BsEyeSlash size={20} /> : <BsEye size={20} />}
                </button>
              </div>
            </div>

            {/* --- Exibição de Erro --- */}
            {erro && (
              <p className="text-sm text-red-600 text-center">
                {erro}
              </p>
            )}

            {/* --- Botão Acessar --- */}
            <button
              type="submit"
              disabled={isLoading}
              className="w-full py-3 px-4 bg-[#1a472a] text-white font-semibold rounded-md shadow-lg hover:bg-green-800 transition duration-300 disabled:bg-gray-400"
            >
              {isLoading ? 'Entrando...' : 'Acessar'}
            </button>
          </form>

          {/* --- Link Cadastre-se --- */}
          <p className="text-center text-gray-600 mt-8">
            Ainda não tem uma conta?{' '}
            <Link href="/register" className="text-green-700 hover:underline font-medium">
              Cadastre-se
            </Link>
          </p>
        </div>
      </div>
    </main>
  );
}