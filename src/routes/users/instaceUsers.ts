import User from "../../models/user";

export const addUser = async (user: User) => {
    try {
        // Asegúrate de que 'user' es un objeto que puede ser serializado
        console.log(`adding ${JSON.stringify(user, null, 2)}`);
        const newUser = await User.create(user);
        return newUser; 
    } catch (error) {
        console.error('Error al crear el usuario:', error); // Añade el logging del error
        throw new Error('No se pudo crear el usuario'); 
    }
};



export const GetUsers = async () => {
  const users = await User.findAll(); // Obtiene todos los usuarios
  return users;
};
