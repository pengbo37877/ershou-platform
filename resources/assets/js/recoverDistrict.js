var districts = [
    {
        "id":"420000",
        "value": "湖北省",
        "childs": [
            {   "id":"420100",
                "value": "武汉市",
                "childs": [
                    {"id":"420101","value":"市辖区"},
                    {"id":"420102","value":"江岸区"},
                    {"id":"420103","value":"江汉区"},
                    {"id":"420104","value":"硚口区"},
                    {"id":"420105","value":"汉阳区"},
                    {"id":"420106","value":"武昌区"},
                    {"id":"420107","value":"青山区"},
                    {"id":"420111","value":"洪山区"},
                    {"id":"420112","value":"东西湖区"},
                    {"id":"420113","value":"汉南区"},
                    {"id":"420114","value":"蔡甸区"},
                    {"id":"420115","value":"江夏区"},
                    {"id":"420116","value":"黄陂区"},
                    {"id":"420117","value":"新洲区"}
                ]
            },
            {   "id":"420200",
                "value": "黄石市",
                "childs": [
                    {"id":"420201","value":"市辖区"},
                    {"id":"420202","value":"黄石港区"},
                    {"id":"420203","value":"西塞山区"},
                    {"id":"420204","value":"下陆区"},
                    {"id":"420205","value":"铁山区"},
                    {"id":"420222","value":"阳新县"},
                    {"id":"420281","value":"大冶市"}
                ]
            },
            {   "id":"420300",
                "value": "十堰市",
                "childs": [
                    {"id":"420301","value":"市辖区"},
                    {"id":"420302","value":"茅箭区"},
                    {"id":"420303","value":"张湾区"},
                    {"id":"420304","value":"郧阳区"},
                    {"id":"420322","value":"郧西县"},
                    {"id":"420323","value":"竹山县"},
                    {"id":"420324","value":"竹溪县"},
                    {"id":"420325","value":"房县"},
                    {"id":"420381","value":"丹江口市"}
                ]
            },
            {   "id":"420500",
                "value": "宜昌市",
                "childs": [
                    {"id":"420501","value":"市辖区"},
                    {"id":"420502","value":"西陵区"},
                    {"id":"420503","value":"伍家岗区"},
                    {"id":"420504","value":"点军区"},
                    {"id":"420505","value":"猇亭区"},
                    {"id":"420506","value":"夷陵区"},
                    {"id":"420525","value":"远安县"},
                    {"id":"420526","value":"兴山县"},
                    {"id":"420527","value":"秭归县"},
                    {"id":"420528","value":"长阳土家族自治县"},
                    {"id":"420529","value":"五峰土家族自治县"},
                    {"id":"420581","value":"宜都市"},
                    {"id":"420582","value":"当阳市"},
                    {"id":"420583","value":"枝江市"}
                ]
            },
            {   "id":"420600",
                "value": "襄阳市",
                "childs": [
                    {"id":"420601","value":"市辖区"},
                    {"id":"420602","value":"襄城区"},
                    {"id":"420606","value":"樊城区"},
                    {"id":"420607","value":"襄州区"},
                    {"id":"420624","value":"南漳县"},
                    {"id":"420625","value":"谷城县"},
                    {"id":"420626","value":"保康县"},
                    {"id":"420682","value":"老河口市"},
                    {"id":"420683","value":"枣阳市"},
                    {"id":"420684","value":"宜城市"}
                ]
            },
            {   "id":"420700",
                "value": "鄂州市",
                "childs": [
                    {"id":"420701","value":"市辖区"},
                    {"id":"420702","value":"梁子湖区"},
                    {"id":"420703","value":"华容区"},
                    {"id":"420704","value":"鄂城区"}
                ]
            },
            {   "id":"420800",
                "value": "荆门市",
                "childs": [
                    {"id":"420801","value":"市辖区"},
                    {"id":"420802","value":"东宝区"},
                    {"id":"420804","value":"掇刀区"},
                    {"id":"420821","value":"京山县"},
                    {"id":"420822","value":"沙洋县"},
                    {"id":"420881","value":"钟祥市"}
                ]
            },
            {   "id":"420900",
                "value": "孝感市",
                "childs": [
                    {"id":"420901","value":"市辖区"},
                    {"id":"420902","value":"孝南区"},
                    {"id":"420921","value":"孝昌县"},
                    {"id":"420922","value":"大悟县"},
                    {"id":"420923","value":"云梦县"},
                    {"id":"420981","value":"应城市"},
                    {"id":"420982","value":"安陆市"},
                    {"id":"420984","value":"汉川市"}
                ]
            },
            {   "id":"421000",
                "value": "荆州市",
                "childs": [
                    {"id":"421001","value":"市辖区"},
                    {"id":"421002","value":"沙市区"},
                    {"id":"421003","value":"荆州区"},
                    {"id":"421022","value":"公安县"},
                    {"id":"421023","value":"监利县"},
                    {"id":"421024","value":"江陵县"},
                    {"id":"421081","value":"石首市"},
                    {"id":"421083","value":"洪湖市"},
                    {"id":"421087","value":"松滋市"}
                ]
            },
            {   "id":"421100",
                "value": "黄冈市",
                "childs": [
                    {"id":"421101","value":"市辖区"},
                    {"id":"421102","value":"黄州区"},
                    {"id":"421121","value":"团风县"},
                    {"id":"421122","value":"红安县"},
                    {"id":"421123","value":"罗田县"},
                    {"id":"421124","value":"英山县"},
                    {"id":"421125","value":"浠水县"},
                    {"id":"421126","value":"蕲春县"},
                    {"id":"421127","value":"黄梅县"},
                    {"id":"421181","value":"麻城市"},
                    {"id":"421182","value":"武穴市"}
                ]
            },
            {   "id":"421200",
                "value": "咸宁市",
                "childs": [
                    {"id":"421201","value":"市辖区"},
                    {"id":"421202","value":"咸安区"},
                    {"id":"421221","value":"嘉鱼县"},
                    {"id":"421222","value":"通城县"},
                    {"id":"421223","value":"崇阳县"},
                    {"id":"421224","value":"通山县"},
                    {"id":"421281","value":"赤壁市"}
                ]
            },
            {   "id":"421300",
                "value": "随州市",
                "childs": [
                    {"id":"421301","value":"市辖区"},
                    {"id":"421303","value":"曾都区"},
                    {"id":"421321","value":"随县"},
                    {"id":"421381","value":"广水市"}
                ]
            },
            {   "id":"422800",
                "value": "恩施土家族苗族自治州",
                "childs": [
                    {"id":"422801","value":"恩施市"},
                    {"id":"422802","value":"利川市"},
                    {"id":"422822","value":"建始县"},
                    {"id":"422823","value":"巴东县"},
                    {"id":"422825","value":"宣恩县"},
                    {"id":"422826","value":"咸丰县"},
                    {"id":"422827","value":"来凤县"},
                    {"id":"422828","value":"鹤峰县"}
                ]
            },
            {   "id":"429000",
                "value": "省直辖县级行政区划",
                "childs": [
                    {"id":"429004","value":"仙桃市"},
                    {"id":"429005","value":"潜江市"},
                    {"id":"429006","value":"天门市"},
                    {"id":"429021","value":"神农架林区"}
                ]
            }]
    },
];

export {districts};