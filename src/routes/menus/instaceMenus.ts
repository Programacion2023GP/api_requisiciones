import Menu from "../../models/menu";

// Define el tipo MenuItem con las propiedades requeridas
interface MenuItem {
    id: number;
    name: string;
    submenu: number | null;
    active: boolean;
    createdAt: string;
    updatedAt: string;
    submenus?: MenuItem[]; // La propiedad submenus es opcional
}

export const GetMenu = async () => {
    // Realizamos la consulta y aseguramos que los resultados se ajusten al tipo MenuItem
    let menu = await Menu.findAll({
        where: {
            active: 1
        }
    });

    // Mapear los resultados a MenuItem
    const menuItems: MenuItem[] = menu.map((item: any) => ({
        id: item.id,
        name: item.name,
        submenu: item.submenu,
        active: item.active,
        createdAt: item.createdAt.toISOString(),  // Aseguramos que sea una cadena de fecha
        updatedAt: item.updatedAt.toISOString(),  // Aseguramos que sea una cadena de fecha
        submenus: [] // Inicializamos los submenús vacíos
    }));

    // Creamos el mapa de menús principales
    const menuMap: { [key: number]: MenuItem } = {};

    // Primero agregamos los menús principales
    menuItems.forEach(item => {
        if (item.submenu === null) {
            menuMap[item.id] = { ...item }; // Agregamos el menú principal sin submenús
        }
    });

    // Creamos un conjunto para rastrear IDs que ya están en los submenús
    const submenuIds = new Set<number>();

    // Agregamos los submenús a sus respectivos menús principales
    menuItems.forEach(item => {
        if (item.submenu !== null && menuMap[item.submenu]) {
            // Solo agregamos el hijo si no está ya en el conjunto de submenús
            if (!submenuIds.has(item.id) && item.id !== item.submenu) {
                // Clonamos el submenuItem y eliminamos la propiedad submenus
                const submenuItem = { ...item };
                delete submenuItem.submenus; // Eliminar la propiedad submenus
                menuMap[item.submenu].submenus!.push(submenuItem);
                submenuIds.add(item.id); // Añadimos el ID al conjunto
            }
        }
    });

    // Filtramos menuItems para eliminar aquellos que ya están en los submenús
    const filteredMenuItems = menuItems.filter(item => !submenuIds.has(item.id));

    // Ahora agregamos los elementos que no tienen submenús como submenús de sí mismos
    filteredMenuItems.forEach(item => {
        if (item.submenus && item.submenus.length === 0) {
            const selfSubmenuItem = { ...item }; // Clonamos el elemento para usarlo como submenú
            delete selfSubmenuItem.submenus; // Eliminar la propiedad submenus
            menuMap[item.id].submenus!.push(selfSubmenuItem); // Agregamos como submenú
        }
    });

    // Retornamos los menús principales con sus submenús
    const finalMenu = Object.values(menuMap);

    return finalMenu;
};
