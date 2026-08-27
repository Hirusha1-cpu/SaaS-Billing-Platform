import React from 'react';
import { useAuth } from '../../Hooks/useAuth';
import { Button } from './Button';
import { UserIcon, Cog6ToothIcon } from '@heroicons/react/24/outline';
import { Link } from 'react-router-dom';

const Navbar = () => {
  const { user, logout } = useAuth();

  return (
    <nav className="bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center">
      <div className="flex items-center gap-4">
        <h2 className="text-lg font-semibold text-gray-800">
          Welcome back, {user?.name}!
        </h2>
      </div>
      
      <div className="flex items-center gap-4">
        <Link to="/settings">
          <button className="p-2 hover:bg-gray-100 rounded-lg">
            <Cog6ToothIcon className="w-5 h-5 text-gray-600" />
          </button>
        </Link>
        
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
            {user?.name?.charAt(0) || 'U'}
          </div>
          <Button variant="ghost" size="sm" onClick={logout}>
            Logout
          </Button>
        </div>
      </div>
    </nav>
  );
};

export default Navbar;