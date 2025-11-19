'use client'
import React, { useState } from 'react';
// Caminho de Importação Ajustado: '../../context/AuthContext'
import { useAuth } from '../../context/AuthContext'; 

const LoginPage = () => {
  const [cpf, setCpf] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  // Acessamos o user diretamente (para o redirecionamento) e a função login
  const { login, user, isLoading } = useAuth();
  
  // Usaremos o useRouter, que é o padrão do Next.js. 
  // O seu problema de compilação era no layout, mas no page.tsx deve usar o useRouter.
  // Vou usar window.location.href para manter a robustez que temos usado.
  const redirectToMe = () => {
    if (typeof window !== 'undefined') {
      // Para next.js, o push via window.location.href é o mais seguro no Canvas
      window.location.href = '/chat'; 
    }
  };


  // Tipando 'e' como React.FormEvent<HTMLFormElement>
  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault(); 
    setError('');
    setIsSubmitting(true);
    
    if (!cpf || !password) {
      setError('Por favor, preencha todos os campos.');
      setIsSubmitting(false);
      return;
    }

    try {
      // Chama a função login do contexto 
      const result = await login({ cpf, password }); 
      
      // NOTA: No código original, se result.success fosse true, ele redirecionava imediatamente.
      // O IDEAL é o AuthContext atualizar o 'user' e o 'if (!isLoading && user)' acima tratar o redirecionamento.
      
      if (result.success) {
        // Em vez de redirecionar AGORA, confiamos que o AuthContext atualizou o 'user' e o 'if' acima fará o trabalho.
        // Se precisar de forçar o refresh do contexto (se o estado não estiver a ser reativo), pode tentar:
        // window.location.reload(); 
        
        // Apenas para feedback imediato na console:
        window.location.href = '/chat';
        return;
        
      } else {
        // Exibe a mensagem de erro que veio do contexto 
        setError(result.message || 'Falha no login. Tente novamente.');
      }
    } catch (err) {
      setError('Ocorreu um erro inesperado na chamada ao servidor.');
      console.error(err);
    } finally {
      // IMPORTANTE: Se o login foi um sucesso, o estado 'isSubmitting' deve ser limpo, 
      // mas o redirecionamento é tratado pelo 'if' no topo do componente, 
      // assim que o AuthContext atualizar o estado do 'user'.
      setIsSubmitting(false);
    }
  };

  return (
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
          disabled={isSubmitting || isLoading} // Desabilitar se estiver carregando do contexto
          className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#1a472a] hover:bg-[#2e623f] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 transition duration-150 ease-in-out"
        >
          {isSubmitting ? 'A entrar...' : 'Entrar'}
        </button>
      </form>
    </div>
  );
};

export default LoginPage;