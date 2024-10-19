import User from "../../models/user";

export const addUser = async () => {
    const newUser = await User.create({
        Name: 'Luis',
        PaternalName: 'Perez',
        MaternalName: 'Lopez',
        Email: 'luis.perez@example.com',
        Password: 'securepassword123',
        Departamento: 'Ventas',
    });

    console.log('User created:', newUser.toJSON());
};

export const GetUsers = async () => {
    const users = await User.findAll(); // Obtiene todos los usuarios
    return users;
}