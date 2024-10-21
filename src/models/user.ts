// src/models/user.ts
import { Model, DataTypes, Optional } from 'sequelize';
import sequelize from '../db'; 
import bcrypt from 'bcrypt';

// Definir los atributos del modelo
interface UserAttributes {
    id?: number;
    Name: string;
    PaternalName: string;
    MaternalName?: string | null;  // MaternalName puede ser string o null
    Email: string;
    Password: string;  // Cambiado a requerido
    Departamento: number; // Asegúrate de que sea un número
}

// Opciones para crear un nuevo usuario (al crear, el ID es opcional)
interface UserCreationAttributes extends Optional<UserAttributes, 'id'> {}

// Definir la clase del modelo con los tipos
class User extends Model<UserAttributes, UserCreationAttributes> implements UserAttributes {
    public id!: number;
    public Name!: string;
    public PaternalName!: string;
    public MaternalName!: string | null;  // Definirla como string | null
    public Email!: string;
    public Password!: string; // Cambiado a requerido
    public Departamento!: number; // Corregido a number

    // Método para verificar la contraseña
    public validatePassword(password: string): boolean {
        return bcrypt.compareSync(password, this.Password);
    }
}

User.init(
    {
        id: {
            type: DataTypes.INTEGER.UNSIGNED,
            autoIncrement: true,
            primaryKey: true,
        },
        Name: {
            type: DataTypes.STRING,
            allowNull: false,
        },
        PaternalName: {
            type: DataTypes.STRING,
            allowNull: false,
        },
        MaternalName: {
            type: DataTypes.STRING,
            allowNull: true, // Puede ser null
        },
        Email: {
            type: DataTypes.STRING,
            allowNull: false,
            unique: true,
        },
        Password: {
            type: DataTypes.STRING,
            allowNull: false,
            defaultValue: "123456" // Esto puede ser reemplazado por un hash
        },
        Departamento: {
            type: DataTypes.INTEGER, // Cambiado a INTEGER
            allowNull: false,
        },
    },
    {
        sequelize,
        modelName: 'User',
        tableName: 'users',
        timestamps: true,
        hooks: {
            beforeSave: async (user: User) => {
                if (user.changed('Password')) {
                    // Hashea la contraseña antes de guardar
                    const salt = await bcrypt.genSalt(10);
                    user.Password = await bcrypt.hash(user.Password, salt);
                }
            },
        },
    }
);

export default User;
