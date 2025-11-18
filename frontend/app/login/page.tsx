'use client'
import React, { useState } from 'react';
// CORREÇÃO FINAL DO CAMINHO: Vamos tentar subir apenas dois níveis.
// Se 'app' e 'context' estiverem dentro de 'src', o 'login' está em 'src/app/login'.
// Para ir para 'src/context', precisamos de: 'src/app/login' -> 'src/app' -> 'src/' -> 'src/context'
// Caminho de Importação Ajustado: '../../context/AuthContext'
import { useAuth } from '../../context/AuthContext'; 

const LoginPage = () => {
  const [cpf, setCpf] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  // 1. Obter a função 'login' do contexto
  const { login, isAuthenticated } = useAuth();
  
  // Redirecionamento: Não usamos useRouter para evitar a dependência 'next/navigation'
  const redirectToMe = () => {
    if (typeof window !== 'undefined') {
      // Para next.js, o push via window.location.href é o mais seguro no Canvas
      window.location.href = '/me'; 
    }
  };

  // Se já estiver autenticado, redireciona imediatamente
  if (isAuthenticated) {
    redirectToMe();
    return null; 
  }

  // Tipando 'e' como React.FormEvent<HTMLFormElement>
  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault(); 
    setError('');
    setIsSubmitting(true);
    
    // Validar rapidamente se os campos estão preenchidos
    if (!cpf || !password) {
      setError('Por favor, preencha todos os campos.');
      setIsSubmitting(false);
      return;
    }

    try {
      // Chama a função login do contexto 
      const result = await login({ cpf, password }); 
      
      if (result.success) {
        console.log("Login OK, a redirecionar...");
        redirectToMe(); 
      } else {
        // Exibe a mensagem de erro que veio do contexto 
        setError(result.message || 'Falha no login. Tente novamente.');
      }
    } catch (err) {
      setError('Ocorreu um erro inesperado.');
      console.error(err);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="flex justify-center items-center min-h-screen bg-gray-50">
      <div className="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-xl">
        <h2 className="text-3xl font-bold text-center text-gray-800">Acesso de Aluno</h2>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label htmlFor="cpf" className="block text-sm font-medium text-gray-700">CPF</label>
            <input
              id="cpf"
              type="text"
              value={cpf}
              onChange={(e) => setCpf(e.target.value)}
              required
              className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
              placeholder="Ex: 12345678900"
            />
          </div>
          <div>
            <label htmlFor="password" className="block text-sm font-medium text-gray-700">Senha</label>
            <input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
              placeholder="********"
            />
          </div>
          
          {error && (
            <p className="text-sm text-red-600 p-2 bg-red-50 rounded-md border border-red-200">
              {error}
            </p>
          )}

          <button
            type="submit"
            disabled={isSubmitting}
            className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#1a472a] hover:bg-[#2e623f] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 transition duration-150 ease-in-out"
          >
            {isSubmitting ? 'A entrar...' : 'Entrar'}
          </button>
        </form>
      </div>
    </div>
  );
};

export default LoginPage;